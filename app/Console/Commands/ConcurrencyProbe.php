<?php

namespace App\Console\Commands;

use App\Enums\CohortStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\RegistrationLink;
use App\Models\User;
use App\Registration\RegisterParticipant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 4 acceptance: N truly concurrent registrations against a limited
 * cohort must never oversell. Forks real OS processes, each with its own
 * MySQL connection and transaction, all racing on the same seat counter.
 *
 * Local/testing only — refuses to run in production.
 */
class ConcurrencyProbe extends Command
{
    protected $signature = 'app:concurrency-probe {--participants=50} {--capacity=10}';

    protected $description = 'Race N parallel registrations against a limited cohort and report the outcome';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to run in production.');

            return self::FAILURE;
        }

        if (! function_exists('pcntl_fork')) {
            $this->error('pcntl is not available.');

            return self::FAILURE;
        }

        $participants = (int) $this->option('participants');
        $capacity = (int) $this->option('capacity');

        // ساحة الاختبار
        $cohort = Cohort::factory()->create(['capacity' => $capacity]);
        $cohort->forceFill(['status' => CohortStatus::Open])->save();

        $link = RegistrationLink::query()->create([
            'cohort_id' => $cohort->id,
            'token' => RegistrationLink::generateToken(),
            'created_by' => User::query()->value('id') ?? User::factory()->create()->id,
        ]);

        DB::disconnect();

        $children = [];

        for ($i = 0; $i < $participants; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->error('Fork failed.');

                return self::FAILURE;
            }

            if ($pid === 0) {
                // child: own connection, one registration, exit
                DB::reconnect();

                try {
                    app(RegisterParticipant::class)->handle($link, [
                        'full_name_ar' => "مشارك {$i}",
                        'email' => "probe-{$i}@omran.local",
                        'phone' => '9000'.str_pad((string) $i, 4, '0'),
                    ]);
                } catch (\Throwable) {
                    // انتهاء المقاعد لا يرمي — أي استثناء آخر يظهر في العد النهائي
                }

                exit(0);
            }

            $children[] = $pid;
        }

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
        }

        DB::reconnect();

        $cohort->refresh();
        $confirmed = Enrollment::query()->where('cohort_id', $cohort->id)->where('status', 'confirmed')->count();
        $waitlisted = Enrollment::query()->where('cohort_id', $cohort->id)->where('status', 'waitlisted')->count();
        $total = Enrollment::query()->where('cohort_id', $cohort->id)->count();

        $this->table(
            ['participants', 'capacity', 'confirmed', 'waitlisted', 'total', 'seats_taken'],
            [[$participants, $capacity, $confirmed, $waitlisted, $total, $cohort->seats_taken]],
        );

        $ok = $confirmed === $capacity
            && $waitlisted === $participants - $capacity
            && $cohort->seats_taken === $capacity;

        $this->{$ok ? 'info' : 'error'}($ok ? 'PASS — no oversell, no lost registrations.' : 'FAIL');

        // تنظيف
        Enrollment::query()->where('cohort_id', $cohort->id)->forceDelete();
        $link->forceDelete();
        $cohort->forceDelete();

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
