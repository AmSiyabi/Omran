<?php

namespace App\Finance\Reports;

use App\Finance\Money;
use App\Models\Account;
use App\Models\JournalLine;
use App\Models\Partner;
use Carbon\CarbonInterface;

/**
 * كشف حساب الشريك (spec §8.10): opening balance, allocations, payouts,
 * running balance, closing — the partner's current account (302x) laid out
 * for a human. Credit-normal: a positive balance is money the center owes
 * the partner.
 */
class PartnerStatement
{
    /**
     * @return array{
     *     partner: Partner,
     *     opening: Money,
     *     rows: list<array{date: string, entry_number: string, description: string, credit: Money, debit: Money, balance: Money}>,
     *     total_credited: Money,
     *     total_debited: Money,
     *     closing: Money,
     * }
     */
    public function build(Partner $partner, CarbonInterface $from, CarbonInterface $to): array
    {
        $account = Account::query()
            ->where('partner_id', $partner->id)
            ->where('code', 'like', '302%')
            ->firstOrFail();

        $openingTotal = (int) JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.account_id', $account->id)
            ->where('journal_entries.entry_date', '<', $from->toDateString())
            ->selectRaw('COALESCE(SUM(journal_lines.credit_baisa - journal_lines.debit_baisa), 0) as total')
            ->toBase()
            ->value('total');

        $opening = new Money($openingTotal);

        $lines = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.account_id', $account->id)
            ->whereBetween('journal_entries.entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_lines.id')
            ->select([
                'journal_lines.debit_baisa', 'journal_lines.credit_baisa', 'journal_lines.memo_ar',
                'journal_entries.entry_date', 'journal_entries.entry_number', 'journal_entries.description_ar',
            ])
            ->toBase()
            ->get();

        $rows = [];
        $running = $opening;
        $credited = 0;
        $debited = 0;

        foreach ($lines as $line) {
            $credit = (int) $line->credit_baisa;
            $debit = (int) $line->debit_baisa;
            $credited += $credit;
            $debited += $debit;
            $running = $running->add(new Money($credit))->subtract(new Money($debit));

            $rows[] = [
                'date' => (string) $line->entry_date,
                'entry_number' => $line->entry_number,
                'description' => $line->memo_ar ?? $line->description_ar,
                'credit' => new Money($credit),
                'debit' => new Money($debit),
                'balance' => $running,
            ];
        }

        return [
            'partner' => $partner,
            'opening' => $opening,
            'rows' => $rows,
            'total_credited' => new Money($credited),
            'total_debited' => new Money($debited),
            'closing' => $running,
        ];
    }

    /**
     * الرصيد الحالي للحساب الجاري — تستخدمه لوحة المؤشرات.
     */
    public function currentBalance(Partner $partner): Money
    {
        $account = Account::query()
            ->where('partner_id', $partner->id)
            ->where('code', 'like', '302%')
            ->first();

        if ($account === null) {
            return Money::zero();
        }

        $total = (int) JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.account_id', $account->id)
            ->selectRaw('COALESCE(SUM(journal_lines.credit_baisa - journal_lines.debit_baisa), 0) as total')
            ->toBase()
            ->value('total');

        return new Money($total);
    }
}
