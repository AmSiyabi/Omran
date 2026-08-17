<?php

namespace App\Console\Commands;

use App\Finance\JournalPoster;
use App\Finance\Money;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 acceptance: entry numbers stay gapless under N concurrent
 * creations — real forked processes racing on the sequence row lock.
 */
class JournalConcurrencyProbe extends Command
{
    protected $signature = 'app:journal-probe {--entries=100}';

    protected $description = 'Race N parallel journal postings and verify gapless sequential numbering';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to run in production.');

            return self::FAILURE;
        }

        $count = (int) $this->option('entries');
        $marker = 'probe-'.getmypid();

        $receivable = Account::byCode('1100')->id;
        $revenue = Account::byCode('4010')->id;
        $userId = User::query()->value('id');

        $before = (int) DB::table('journal_sequences')->where('year', now()->year)->value('last_number');

        DB::disconnect();

        $children = [];

        for ($i = 0; $i < $count; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->error('Fork failed.');

                return self::FAILURE;
            }

            if ($pid === 0) {
                DB::reconnect();

                try {
                    app(JournalPoster::class)->post(
                        now(),
                        $marker,
                        [
                            ['account_id' => $receivable, 'debit' => new Money(1000 + $i)],
                            ['account_id' => $revenue, 'credit' => new Money(1000 + $i)],
                        ],
                        $userId,
                    );
                } catch (\Throwable $e) {
                    fwrite(STDERR, $e->getMessage()."\n");
                    exit(1);
                }

                exit(0);
            }

            $children[] = $pid;
        }

        $failures = 0;

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);

            if (pcntl_wexitstatus($status) !== 0) {
                $failures++;
            }
        }

        DB::reconnect();

        $numbers = JournalEntry::query()
            ->where('description_ar', $marker)
            ->pluck('entry_number')
            ->map(fn (string $number) => (int) substr($number, -6))
            ->sort()
            ->values();

        $expected = range($before + 1, $before + $count);
        $gapless = $numbers->all() === $expected;

        $this->table(
            ['entries', 'failures', 'min', 'max', 'distinct', 'gapless'],
            [[$count, $failures, $numbers->min(), $numbers->max(), $numbers->unique()->count(), $gapless ? 'yes' : 'NO']],
        );

        $this->{$gapless && $failures === 0 ? 'info' : 'error'}(
            $gapless && $failures === 0 ? 'PASS — gapless sequential numbering under concurrency.' : 'FAIL'
        );

        return $gapless && $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
