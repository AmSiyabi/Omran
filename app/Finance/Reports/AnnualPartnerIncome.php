<?php

namespace App\Finance\Reports;

use App\Finance\Money;
use App\Models\Account;
use App\Models\JournalLine;
use App\Models\Partner;

/**
 * الملخص السنوي للشريك (spec §8.10): total allocations and payouts per
 * calendar year — three clean years of records before PIT lands in 2028.
 */
class AnnualPartnerIncome
{
    /**
     * @return array{
     *     year: int,
     *     rows: list<array{partner: Partner, allocated: Money, paid_out: Money, net_movement: Money}>,
     * }
     */
    public function build(int $year): array
    {
        $partners = Partner::query()->where('is_active', true)->orderBy('id')->get();
        $rows = [];

        foreach ($partners as $partner) {
            $account = Account::query()
                ->where('partner_id', $partner->id)
                ->where('code', 'like', '302%')
                ->first();

            if ($account === null) {
                continue;
            }

            $sums = JournalLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_entries.status', 'posted')
                ->where('journal_lines.account_id', $account->id)
                ->whereBetween('journal_entries.entry_date', ["{$year}-01-01", "{$year}-12-31"])
                ->selectRaw('COALESCE(SUM(journal_lines.credit_baisa), 0) as credited, COALESCE(SUM(journal_lines.debit_baisa), 0) as debited')
                ->toBase()
                ->first();

            $rows[] = [
                'partner' => $partner,
                'allocated' => new Money((int) $sums->credited),
                'paid_out' => new Money((int) $sums->debited),
                'net_movement' => new Money((int) $sums->credited - (int) $sums->debited),
            ];
        }

        return ['year' => $year, 'rows' => $rows];
    }
}
