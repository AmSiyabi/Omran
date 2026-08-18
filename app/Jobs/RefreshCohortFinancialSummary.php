<?php

namespace App\Jobs;

use App\Enums\CohortStatus;
use App\Models\Cohort;
use App\Models\JournalLine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Spec §11.2: materialized per-cohort financials, refreshed on journal
 * write. Dashboards and the P&L report read this, never the raw journal.
 */
class RefreshCohortFinancialSummary implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $cohortId,
    ) {}

    public function handle(): void
    {
        $cohort = Cohort::query()->find($this->cohortId);

        if ($cohort === null) {
            return;
        }

        $sums = JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.cohort_id', $cohort->id)
            ->where('journal_entries.status', 'posted')
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(CASE WHEN accounts.code LIKE '4%' THEN journal_lines.credit_baisa - journal_lines.debit_baisa ELSE 0 END), 0) as gross_revenue,
                COALESCE(SUM(CASE WHEN accounts.code = '1100' THEN journal_lines.credit_baisa ELSE 0 END), 0) as collected,
                COALESCE(SUM(CASE WHEN accounts.code = '1100' THEN journal_lines.debit_baisa - journal_lines.credit_baisa ELSE 0 END), 0) as receivable,
                COALESCE(SUM(CASE WHEN accounts.code LIKE '5%' AND accounts.code NOT IN ('5010', '5020') THEN journal_lines.debit_baisa - journal_lines.credit_baisa ELSE 0 END), 0) as direct_costs,
                COALESCE(SUM(CASE WHEN accounts.code IN ('5010', '5020') THEN journal_lines.debit_baisa - journal_lines.credit_baisa ELSE 0 END), 0) as deliverer_share
            SQL)
            ->toBase()
            ->first();

        $gross = (int) $sums->gross_revenue;
        $directCosts = (int) $sums->direct_costs;
        $delivererShare = (int) $sums->deliverer_share;
        $centerShare = $gross - $directCosts - $delivererShare;

        DB::table('cohort_financial_summaries')->updateOrInsert(
            ['cohort_id' => $cohort->id],
            [
                'gross_revenue_baisa' => $gross,
                'collected_baisa' => (int) $sums->collected,
                'receivable_baisa' => (int) $sums->receivable,
                'direct_costs_baisa' => $directCosts,
                'deliverer_share_baisa' => $delivererShare,
                'center_share_baisa' => $centerShare,
                'net_result_baisa' => $gross - $directCosts,
                'margin_basis_points' => $gross > 0 ? intdiv($centerShare * 10000, $gross) : 0,
                'is_settled' => $cohort->status === CohortStatus::Settled,
                'refreshed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
