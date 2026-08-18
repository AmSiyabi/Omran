<?php

namespace App\Finance\Reports;

use App\Finance\Money;
use App\Models\Cohort;
use App\Models\JournalLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ربحية الدورة (spec §8.10) — the most-used report. The list reads the
 * materialized summaries (§11.2); the detail view breaks costs down by
 * account from the journal.
 */
class CohortProfitability
{
    /**
     * @return Collection<int, \stdClass>
     */
    public function list(): Collection
    {
        return DB::table('cohort_financial_summaries')
            ->join('cohorts', 'cohorts.id', '=', 'cohort_financial_summaries.cohort_id')
            ->join('courses', 'courses.id', '=', 'cohorts.course_id')
            ->orderByDesc('cohort_financial_summaries.gross_revenue_baisa')
            ->select([
                'cohort_financial_summaries.*',
                'cohorts.code as cohort_code',
                'cohorts.status as cohort_status',
                'cohorts.title_override_ar',
                'courses.title_ar as course_title',
            ])
            ->get();
    }

    /**
     * @return array{
     *     cohort: Cohort,
     *     revenue: Money,
     *     collected: Money,
     *     receivable: Money,
     *     cost_lines: list<array{code: string, name_ar: string, amount: Money}>,
     *     direct_costs_total: Money,
     *     deliverer_share: Money,
     *     center_share: Money,
     *     margin_basis_points: int,
     * }
     */
    public function detail(Cohort $cohort): array
    {
        $summary = DB::table('cohort_financial_summaries')->where('cohort_id', $cohort->id)->first();

        $costLines = JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.cohort_id', $cohort->id)
            ->where('accounts.code', 'like', '5%')
            ->groupBy('accounts.code', 'accounts.name_ar')
            ->selectRaw('accounts.code, accounts.name_ar, COALESCE(SUM(journal_lines.debit_baisa - journal_lines.credit_baisa), 0) as total')
            ->orderBy('accounts.code')
            ->toBase()
            ->get()
            ->filter(fn ($row) => (int) $row->total !== 0)
            ->map(fn ($row) => [
                'code' => $row->code,
                'name_ar' => $row->name_ar,
                'amount' => new Money((int) $row->total),
            ])
            ->values()
            ->all();

        return [
            'cohort' => $cohort,
            'revenue' => new Money((int) ($summary->gross_revenue_baisa ?? 0)),
            'collected' => new Money((int) ($summary->collected_baisa ?? 0)),
            'receivable' => new Money((int) ($summary->receivable_baisa ?? 0)),
            'cost_lines' => $costLines,
            'direct_costs_total' => new Money((int) ($summary->direct_costs_baisa ?? 0)),
            'deliverer_share' => new Money((int) ($summary->deliverer_share_baisa ?? 0)),
            'center_share' => new Money((int) ($summary->center_share_baisa ?? 0)),
            'margin_basis_points' => (int) ($summary->margin_basis_points ?? 0),
        ];
    }
}
