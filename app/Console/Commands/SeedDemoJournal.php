<?php

namespace App\Console\Commands;

use App\Finance\VatMonitor;
use App\Models\Account;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Perf fixture for the Phase 6 acceptance gate (dashboard < 400ms at 5,000
 * entries). Bulk inserts balanced demo entries directly — deliberately
 * bypassing JournalPoster, which would take minutes one entry at a time.
 * Deterministic (seeded PRNG) so repeated runs produce the same ledger.
 */
class SeedDemoJournal extends Command
{
    protected $signature = 'app:seed-demo-journal {count=5000} {--force : Allow running outside local/testing}';

    protected $description = 'Bulk-seed balanced demo journal entries for dashboard/report performance testing';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to seed demo data in production. Use --force if you really mean it.');

            return self::FAILURE;
        }

        $count = max(1, (int) $this->argument('count'));

        $accounts = Account::query()
            ->whereIn('code', ['1020', '1100', '4010', '5030', '6010'])
            ->pluck('id', 'code');

        if ($accounts->count() < 5) {
            $this->error('Chart of accounts missing — run the ChartOfAccountsSeeder first.');

            return self::FAILURE;
        }

        $userId = User::query()->orderBy('id')->value('id');

        if ($userId === null) {
            $this->error('No users exist — seed a user first.');

            return self::FAILURE;
        }

        mt_srand(42);

        $startId = ((int) DB::table('journal_entries')->max('id')) + 1;
        $lineId = ((int) DB::table('journal_lines')->max('id')) + 1;
        $now = now()->toDateTimeString();

        // أرقام القيود متسلسلة لكل سنة عبر journal_sequences (spec §8.3)
        $years = [];
        $entries = [];
        $lines = [];

        for ($i = 0; $i < $count; $i++) {
            $entryId = $startId + $i;
            $date = Carbon::now()->subDays(mt_rand(0, 540));
            $year = $date->year;

            if (! isset($years[$year])) {
                DB::table('journal_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);
                $years[$year] = (int) DB::table('journal_sequences')->where('year', $year)->value('last_number');
            }

            $number = ++$years[$year];
            $amount = mt_rand(50, 2000) * 1000;
            $kind = mt_rand(1, 100);

            [$debitCode, $creditCode, $description] = match (true) {
                $kind <= 55 => ['1100', '4010', 'فاتورة تدريب — بيانات تجريبية'],
                $kind <= 80 => ['1020', '1100', 'تحصيل من عميل — بيانات تجريبية'],
                $kind <= 92 => ['5030', '1020', 'إعلانات — بيانات تجريبية'],
                default => ['6010', '1020', 'مصروف تشغيلي — بيانات تجريبية'],
            };

            $entries[] = [
                'id' => $entryId,
                'entry_number' => sprintf('JE-%d-%06d', $year, $number),
                'entry_date' => $date->toDateString(),
                'description_ar' => $description,
                'status' => 'posted',
                'created_by' => $userId,
                'created_at' => $now,
            ];

            $lines[] = [
                'id' => $lineId++,
                'journal_entry_id' => $entryId,
                'account_id' => $accounts[$debitCode],
                'debit_baisa' => $amount,
                'credit_baisa' => 0,
                'line_order' => 0,
                'vat_treatment' => null,
                'created_at' => $now,
            ];
            $lines[] = [
                'id' => $lineId++,
                'journal_entry_id' => $entryId,
                'account_id' => $accounts[$creditCode],
                'debit_baisa' => 0,
                'credit_baisa' => $amount,
                'line_order' => 1,
                'vat_treatment' => $creditCode === '4010' ? 'standard' : null,
                'created_at' => $now,
            ];
        }

        DB::transaction(function () use ($entries, $lines, $years): void {
            foreach (array_chunk($entries, 500) as $chunk) {
                DB::table('journal_entries')->insert($chunk);
            }

            foreach (array_chunk($lines, 500) as $chunk) {
                DB::table('journal_lines')->insert($chunk);
            }

            foreach ($years as $year => $lastNumber) {
                DB::table('journal_sequences')->where('year', $year)->update(['last_number' => $lastNumber]);
            }
        });

        app(VatMonitor::class)->recompute();

        $this->info(sprintf('Seeded %d demo entries (%d lines).', count($entries), count($lines)));

        return self::SUCCESS;
    }
}
