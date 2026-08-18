<?php

namespace App\Finance;

use App\Enums\JournalEntryStatus;
use App\Jobs\RecomputeVatRollingTotal;
use App\Jobs\RefreshCohortFinancialSummary;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * The only way journal entries come into existence (spec §8.3).
 *
 * Invariants enforced here and asserted before any write:
 *  1. SUM(debits) === SUM(credits), exact to the baisa
 *  2. every line has exactly one of debit/credit non-zero
 *  3. no negative amounts
 *  4. entry numbers are gapless and sequential per year, generated inside
 *     the same transaction under a row lock
 */
class JournalPoster
{
    /**
     * @param  list<array{account_id: int, debit?: Money, credit?: Money, partner_id?: ?int, cohort_id?: ?int, memo_ar?: ?string, vat_treatment?: ?string}>  $lines
     */
    public function post(
        CarbonInterface $entryDate,
        string $descriptionAr,
        array $lines,
        int $createdBy,
        ?int $cohortId = null,
        ?int $invoicingEntityId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): JournalEntry {
        $this->assertBalanced($lines);

        // خارج المعاملة (autocommit): وجود صف السنة مضمون قبل القفل — تجنّب
        // ترقية قفل مشترك إلى حصري (مصدر deadlock تحت التوازي)
        DB::table('journal_sequences')->insertOrIgnore(['year' => $entryDate->year, 'last_number' => 0]);

        return DB::transaction(function () use (
            $entryDate, $descriptionAr, $lines, $createdBy,
            $cohortId, $invoicingEntityId, $referenceType, $referenceId,
        ): JournalEntry {
            $entry = JournalEntry::query()->create([
                'entry_number' => $this->nextEntryNumber($entryDate->year),
                'entry_date' => $entryDate->toDateString(),
                'description_ar' => $descriptionAr,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'cohort_id' => $cohortId,
                'invoicing_entity_id' => $invoicingEntityId,
                'status' => JournalEntryStatus::Posted,
                'created_by' => $createdBy,
            ]);

            $accountCodes = Account::query()
                ->whereIn('id', array_column($lines, 'account_id'))
                ->pluck('code', 'id');

            $touchesRevenue = false;

            foreach ($lines as $order => $line) {
                $isRevenueLine = str_starts_with((string) $accountCodes->get($line['account_id']), '4');
                $touchesRevenue = $touchesRevenue || $isRevenueLine;

                JournalLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit_baisa' => ($line['debit'] ?? Money::zero())->baisa,
                    'credit_baisa' => ($line['credit'] ?? Money::zero())->baisa,
                    'partner_id' => $line['partner_id'] ?? null,
                    'cohort_id' => $line['cohort_id'] ?? $cohortId,
                    'memo_ar' => $line['memo_ar'] ?? null,
                    'line_order' => $order,
                    // spec Phase 6: كل سطر إيراد يحمل معاملة ضريبية — الافتراض standard
                    'vat_treatment' => $isRevenueLine ? ($line['vat_treatment'] ?? 'standard') : null,
                ]);
            }

            // §11.2 القوائم تقرأ الملخصات؛ §8.9 المراقب يُحدَّث عند كل ترحيل إيراد
            if ($cohortId !== null) {
                RefreshCohortFinancialSummary::dispatch($cohortId)->afterCommit();
            }

            if ($touchesRevenue) {
                RecomputeVatRollingTotal::dispatch()->afterCommit();
            }

            return $entry;
        });
    }

    /**
     * Correction path (spec §8.3 invariant 4): a reversing entry with debits
     * and credits swapped, linked to the original, which flips to reversed.
     */
    public function reverse(JournalEntry $original, int $createdBy, ?string $reasonAr = null): JournalEntry
    {
        if ($original->status !== JournalEntryStatus::Posted) {
            throw new DomainException('Only posted entries can be reversed.');
        }

        return DB::transaction(function () use ($original, $createdBy, $reasonAr): JournalEntry {
            $lines = $original->lines()->get()->map(fn (JournalLine $line) => [
                'account_id' => $line->account_id,
                'debit' => $line->credit_baisa,
                'credit' => $line->debit_baisa,
                'partner_id' => $line->partner_id,
                'cohort_id' => $line->cohort_id,
                'memo_ar' => $line->memo_ar,
            ])->all();

            $reversal = $this->post(
                entryDate: now(),
                descriptionAr: 'قيد عكسي للقيد '.$original->entry_number.($reasonAr !== null ? ' — '.$reasonAr : ''),
                lines: $lines,
                createdBy: $createdBy,
                cohortId: $original->cohort_id,
                invoicingEntityId: $original->invoicing_entity_id,
                referenceType: 'reversal',
                referenceId: $original->id,
            );

            $original->status = JournalEntryStatus::Reversed;
            $original->reversed_by_entry_id = $reversal->id;
            $original->save();

            return $reversal;
        });
    }

    /**
     * @param  list<array{account_id: int, debit?: Money, credit?: Money}>  $lines
     */
    protected function assertBalanced(array $lines): void
    {
        if ($lines === []) {
            throw new DomainException('A journal entry needs at least two lines.');
        }

        $debits = 0;
        $credits = 0;

        foreach ($lines as $line) {
            $debit = $line['debit'] ?? Money::zero();
            $credit = $line['credit'] ?? Money::zero();

            if ($debit->isNegative() || $credit->isNegative()) {
                throw new DomainException('Journal lines never carry negative amounts (spec §8.3).');
            }

            $debitSet = ! $debit->isZero();
            $creditSet = ! $credit->isZero();

            if ($debitSet === $creditSet) {
                throw new DomainException('Every journal line has exactly one of debit or credit non-zero (spec §8.3).');
            }

            $debits += $debit->baisa;
            $credits += $credit->baisa;
        }

        if ($debits !== $credits) {
            throw new DomainException(
                "Journal entry does not balance: debits {$debits} ≠ credits {$credits} (spec §8.3)."
            );
        }
    }

    protected function nextEntryNumber(int $year): string
    {
        // UPDATE أولاً — يأخذ القفل الحصري في خطوة واحدة، فتتسلسل المعاملات
        // المتوازية دون أي deadlock، ويبقى الترقيم بلا فجوات عند التراجع
        DB::table('journal_sequences')
            ->where('year', $year)
            ->update(['last_number' => DB::raw('last_number + 1')]);

        $next = (int) DB::table('journal_sequences')
            ->where('year', $year)
            ->value('last_number');

        return sprintf('JE-%d-%06d', $year, $next);
    }
}
