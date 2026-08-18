<?php

namespace App\Finance\Reports;

use App\Finance\Money;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * قائمة الدخل (spec §8.10): revenue − direct costs − opex = net, with an
 * accrual/cash toggle. Cash basis classifies only entries that move cash.
 */
class IncomeStatement
{
    /**
     * @return array{
     *     basis: string,
     *     revenue: list<array{code: string, name_ar: string, amount: Money}>,
     *     revenue_total: Money,
     *     direct_costs: list<array{code: string, name_ar: string, amount: Money}>,
     *     direct_costs_total: Money,
     *     opex: list<array{code: string, name_ar: string, amount: Money}>,
     *     opex_total: Money,
     *     net: Money,
     * }
     */
    public function build(CarbonInterface $from, CarbonInterface $to, string $basis = 'accrual'): array
    {
        $revenue = $this->groupByAccount('4', $from, $to, creditNormal: true, cashBasis: $basis === 'cash');
        $directCosts = $this->groupByAccount('5', $from, $to, creditNormal: false, cashBasis: $basis === 'cash');
        $opex = $this->groupByAccount('6', $from, $to, creditNormal: false, cashBasis: $basis === 'cash');

        if ($basis === 'cash') {
            // الأساس النقدي: الإيراد هو المحصَّل فعلاً — التحصيلات من الذمم
            // بالإضافة إلى أي بيع نقدي مباشر (سطر 4xxx في قيد يلمس النقد)
            $collections = (int) $this->linesQuery($from, $to)
                ->where('accounts.code', '1100')
                ->whereExists(fn ($query) => $this->sameEntryTouchesCash($query))
                ->selectRaw('COALESCE(SUM(journal_lines.credit_baisa - journal_lines.debit_baisa), 0) as total')
                ->toBase()
                ->value('total');

            $cashRevenueTotal = array_sum(array_map(fn ($row) => $row['amount']->baisa, $revenue)) + $collections;

            $revenue[] = [
                'code' => '1100',
                'name_ar' => 'تحصيلات من الذمم المدينة',
                'amount' => new Money($collections),
            ];
            $revenueTotal = new Money($cashRevenueTotal);
        } else {
            $revenueTotal = new Money(array_sum(array_map(fn ($row) => $row['amount']->baisa, $revenue)));
        }

        $directTotal = new Money(array_sum(array_map(fn ($row) => $row['amount']->baisa, $directCosts)));
        $opexTotal = new Money(array_sum(array_map(fn ($row) => $row['amount']->baisa, $opex)));

        return [
            'basis' => $basis,
            'revenue' => $revenue,
            'revenue_total' => $revenueTotal,
            'direct_costs' => $directCosts,
            'direct_costs_total' => $directTotal,
            'opex' => $opex,
            'opex_total' => $opexTotal,
            'net' => $revenueTotal->subtract($directTotal)->subtract($opexTotal),
        ];
    }

    /**
     * @return list<array{code: string, name_ar: string, amount: Money}>
     */
    protected function groupByAccount(string $prefix, CarbonInterface $from, CarbonInterface $to, bool $creditNormal, bool $cashBasis): array
    {
        $query = $this->linesQuery($from, $to)
            ->where('accounts.code', 'like', $prefix.'%');

        if ($cashBasis) {
            $query->whereExists(fn ($sub) => $this->sameEntryTouchesCash($sub));
        }

        $direction = $creditNormal
            ? 'journal_lines.credit_baisa - journal_lines.debit_baisa'
            : 'journal_lines.debit_baisa - journal_lines.credit_baisa';

        return $query
            ->groupBy('accounts.code', 'accounts.name_ar')
            ->selectRaw("accounts.code, accounts.name_ar, COALESCE(SUM({$direction}), 0) as total")
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
    }

    /**
     * @return Builder<JournalLine>
     */
    protected function linesQuery(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString()]);
    }

    protected function sameEntryTouchesCash(mixed $query): void
    {
        $query->selectRaw('1')
            ->from('journal_lines as cash_lines')
            ->join('accounts as cash_accounts', 'cash_accounts.id', '=', 'cash_lines.account_id')
            ->whereColumn('cash_lines.journal_entry_id', 'journal_lines.journal_entry_id')
            ->whereIn('cash_accounts.code', ['1010', '1020', '1030']);
    }
}
