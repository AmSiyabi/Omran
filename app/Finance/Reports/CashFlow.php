<?php

namespace App\Finance\Reports;

use App\Finance\Money;
use App\Models\JournalLine;
use Carbon\CarbonInterface;

/**
 * التدفق النقدي بالطريقة المباشرة (spec §8.10): cash in, cash out, net
 * change, closing balance — the simplest correct form. Movements are
 * classified by the counterpart side of each cash entry.
 */
class CashFlow
{
    /** @var array<string, string> تصنيف الطرف المقابل */
    private const CATEGORIES = [
        'collections' => 'تحصيلات من العملاء',
        'cash_sales' => 'مبيعات نقدية مباشرة',
        'contributions' => 'مساهمات رأس مال',
        'direct_costs' => 'تكاليف دورات مدفوعة',
        'opex' => 'مصروفات تشغيلية مدفوعة',
        'payouts' => 'مسحوبات الشركاء',
        'trainer_payments' => 'مدفوعات مدربين خارجيين',
        'other_in' => 'مقبوضات أخرى',
        'other_out' => 'مدفوعات أخرى',
    ];

    /**
     * @return array{
     *     opening: Money,
     *     inflows: list<array{key: string, label: string, amount: Money}>,
     *     inflows_total: Money,
     *     outflows: list<array{key: string, label: string, amount: Money}>,
     *     outflows_total: Money,
     *     net_change: Money,
     *     closing: Money,
     * }
     */
    public function build(CarbonInterface $from, CarbonInterface $to): array
    {
        $opening = $this->cashBalanceBefore($from);

        $movements = JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereIn('accounts.code', ['1010', '1020', '1030'])
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString()])
            ->select('journal_lines.journal_entry_id', 'journal_lines.debit_baisa', 'journal_lines.credit_baisa')
            ->toBase()
            ->get();

        $entryIds = $movements->pluck('journal_entry_id')->unique()->values();

        // الطرف المقابل لكل قيد نقدي — سطر واحد غير نقدي يكفي للتصنيف
        $counterparts = JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('journal_lines.journal_entry_id', $entryIds)
            ->whereNotIn('accounts.code', ['1010', '1020', '1030'])
            ->select('journal_lines.journal_entry_id', 'accounts.code')
            ->toBase()
            ->get()
            ->groupBy('journal_entry_id')
            ->map(fn ($lines) => $lines->first()->code);

        $inflows = [];
        $outflows = [];

        foreach ($movements as $movement) {
            $counterpartCode = (string) ($counterparts[$movement->journal_entry_id] ?? '');
            $inAmount = (int) $movement->debit_baisa;
            $outAmount = (int) $movement->credit_baisa;

            if ($inAmount > 0) {
                $key = match (true) {
                    $counterpartCode === '1100' => 'collections',
                    str_starts_with($counterpartCode, '4') => 'cash_sales',
                    str_starts_with($counterpartCode, '301') => 'contributions',
                    default => 'other_in',
                };
                $inflows[$key] = ($inflows[$key] ?? 0) + $inAmount;
            }

            if ($outAmount > 0) {
                $key = match (true) {
                    str_starts_with($counterpartCode, '5') => 'direct_costs',
                    str_starts_with($counterpartCode, '6') => 'opex',
                    str_starts_with($counterpartCode, '302') => 'payouts',
                    $counterpartCode === '2020' => 'trainer_payments',
                    default => 'other_out',
                };
                $outflows[$key] = ($outflows[$key] ?? 0) + $outAmount;
            }
        }

        $inflowRows = $this->rows($inflows);
        $outflowRows = $this->rows($outflows);
        $inflowsTotal = new Money(array_sum($inflows));
        $outflowsTotal = new Money(array_sum($outflows));
        $netChange = $inflowsTotal->subtract($outflowsTotal);

        return [
            'opening' => $opening,
            'inflows' => $inflowRows,
            'inflows_total' => $inflowsTotal,
            'outflows' => $outflowRows,
            'outflows_total' => $outflowsTotal,
            'net_change' => $netChange,
            'closing' => $opening->add($netChange),
        ];
    }

    public function cashBalanceBefore(CarbonInterface $date): Money
    {
        $total = (int) JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereIn('accounts.code', ['1010', '1020', '1030'])
            ->where('journal_entries.entry_date', '<', $date->toDateString())
            ->selectRaw('COALESCE(SUM(journal_lines.debit_baisa - journal_lines.credit_baisa), 0) as total')
            ->toBase()
            ->value('total');

        return new Money($total);
    }

    /**
     * @param  array<string, int>  $amounts
     * @return list<array{key: string, label: string, amount: Money}>
     */
    protected function rows(array $amounts): array
    {
        $rows = [];

        foreach (self::CATEGORIES as $key => $label) {
            if (($amounts[$key] ?? 0) !== 0) {
                $rows[] = ['key' => $key, 'label' => $label, 'amount' => new Money($amounts[$key])];
            }
        }

        return $rows;
    }
}
