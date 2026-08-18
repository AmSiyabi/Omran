<?php

namespace App\Finance;

use App\Enums\CohortStatus;
use App\Finance\Reports\PartnerStatement;
use App\Models\Cohort;
use App\Models\JournalLine;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Collection;

/**
 * أرقام لوحة المؤشرات (spec §2.2): indexed sums over journal_lines —
 * fast enough for the 400ms budget at thousands of entries.
 */
class DashboardMetrics
{
    public function __construct(
        protected VatMonitor $vatMonitor,
        protected PartnerStatement $partnerStatement,
    ) {}

    /**
     * @return array{
     *     cash: array<int|string, Money>,
     *     cash_total: Money,
     *     mtd_revenue: Money,
     *     receivables: Money,
     *     partner_balances: list<array{partner: Partner, balance: Money}>,
     *     vat: array{taxable: Money, mandatory: Money, voluntary: Money, state: string, percent_of_mandatory: int},
     *     upcoming_cohorts: Collection<int, Cohort>,
     * }
     */
    public function build(): array
    {
        $balances = JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereIn('accounts.code', ['1010', '1020', '1030', '1100'])
            ->groupBy('accounts.code')
            ->selectRaw('accounts.code, COALESCE(SUM(journal_lines.debit_baisa - journal_lines.credit_baisa), 0) as balance')
            ->toBase()
            ->pluck('balance', 'code');

        $monthStart = now()->timezone(config('app.display_timezone'))->startOfMonth()->toDateString();

        $mtdRevenue = (int) JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('accounts.code', 'like', '4%')
            ->where('journal_entries.entry_date', '>=', $monthStart)
            ->selectRaw('COALESCE(SUM(journal_lines.credit_baisa - journal_lines.debit_baisa), 0) as total')
            ->toBase()
            ->value('total');

        $cash = [
            '1010' => new Money((int) ($balances['1010'] ?? 0)),
            '1020' => new Money((int) ($balances['1020'] ?? 0)),
            '1030' => new Money((int) ($balances['1030'] ?? 0)),
        ];

        $partnerBalances = Partner::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(fn (Partner $partner) => [
                'partner' => $partner,
                'balance' => $this->partnerStatement->currentBalance($partner),
            ])
            ->all();

        return [
            'cash' => $cash,
            'cash_total' => new Money(array_sum(array_map(fn (Money $m) => $m->baisa, $cash))),
            'mtd_revenue' => new Money($mtdRevenue),
            'receivables' => new Money((int) ($balances['1100'] ?? 0)),
            'partner_balances' => $partnerBalances,
            'vat' => $this->vatMonitor->status(),
            'upcoming_cohorts' => Cohort::query()
                ->whereIn('status', [CohortStatus::Announced, CohortStatus::Open])
                ->where('starts_at', '>=', now())
                ->with('course:id,title_ar')
                ->orderBy('starts_at')
                ->take(5)
                ->get(),
        ];
    }
}
