<?php

namespace App\Finance;

use App\Enums\CohortStatus;
use App\Enums\SettlementStatus;
use App\Enums\SettlementType;
use App\Models\Account;
use App\Models\Cohort;
use App\Models\JournalEntry;
use App\Models\Settlement;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Settlement lifecycle (spec §8.5): compute (draft, recalculable) → owner
 * reviews → confirm (snapshot frozen, journal posted, immutable).
 * Losses block pending explicit confirmation; overcommitted fixed fees block
 * pending an owner override with a written reason (Appendix B #2/#3).
 */
class SettlementService
{
    public function __construct(
        protected DistributionEngine $engine,
        protected JournalPoster $poster,
    ) {}

    public function computeCohortDraft(Cohort $cohort): Settlement
    {
        if ($cohort->status !== CohortStatus::Delivered) {
            throw new DomainException('Only delivered cohorts can be settled.');
        }

        if (Settlement::query()->where('cohort_id', $cohort->id)->whereIn('status', ['draft', 'posted'])->exists()) {
            throw new DomainException('This cohort already has an open or posted settlement.');
        }

        $result = $this->engine->compute($cohort);

        return Settlement::query()->create([
            'settlement_number' => $this->nextNumber(),
            'type' => SettlementType::Cohort,
            'cohort_id' => $cohort->id,
            ...$this->amountColumns($result),
            'status' => SettlementStatus::Draft,
            'computed_at' => now(),
            'snapshot' => $result->toSnapshot(),
        ]);
    }

    /**
     * Draft is recalculable until confirmed (spec §8.5 workflow).
     */
    public function recompute(Settlement $settlement): Settlement
    {
        $this->assertDraft($settlement);

        $result = $this->engine->compute($settlement->cohort);

        $settlement->update([
            ...$this->amountColumns($result),
            'computed_at' => now(),
            'snapshot' => $result->toSnapshot(),
        ]);

        return $settlement->refresh();
    }

    public function confirm(Settlement $settlement, int $confirmedBy, bool $acceptLoss = false, ?string $overrideReasonAr = null): Settlement
    {
        $this->assertDraft($settlement);

        return DB::transaction(function () use ($settlement, $confirmedBy, $acceptLoss, $overrideReasonAr): Settlement {
            $cohort = Cohort::query()->lockForUpdate()->findOrFail($settlement->cohort_id);

            // إعادة الحساب لحظة التأكيد — اللقطة المجمدة تطابق الدفاتر تماماً
            $result = $this->engine->compute($cohort);

            if (in_array('LOSS', $result->flags, true) && ! $acceptLoss) {
                throw new DomainException('LOSS');
            }

            if (in_array('OVERCOMMITTED', $result->flags, true) && ($overrideReasonAr === null || trim($overrideReasonAr) === '')) {
                throw new DomainException('OVERCOMMITTED');
            }

            $entry = $this->postSettlementJournal($cohort, $result, $confirmedBy);

            $settlement->update([
                ...$this->amountColumns($result),
                'status' => SettlementStatus::Posted,
                'journal_entry_id' => $entry->id,
                'computed_at' => now(),
                'confirmed_by' => $confirmedBy,
                'confirmed_at' => now(),
                'notes_ar' => $overrideReasonAr !== null && trim($overrideReasonAr) !== ''
                    ? trim(($settlement->notes_ar !== null ? $settlement->notes_ar."\n" : '').'سبب التجاوز: '.$overrideReasonAr)
                    : $settlement->notes_ar,
                'snapshot' => $result->toSnapshot() + [
                    'confirmed_at' => now()->toIso8601String(),
                    'journal_entry_number' => $entry->entry_number,
                    'accepted_loss' => $acceptLoss,
                    'override_reason' => $overrideReasonAr,
                ],
            ]);

            $cohort->transitionTo(CohortStatus::Settled);

            return $settlement->refresh();
        });
    }

    /**
     * Reversal: reversing journal entry, settlement marked reversed, cohort
     * reopened to delivered so it can be settled again (D-039).
     */
    public function reverse(Settlement $settlement, int $reversedBy, string $reasonAr): Settlement
    {
        if ($settlement->status !== SettlementStatus::Posted) {
            throw new DomainException('Only posted settlements can be reversed.');
        }

        return DB::transaction(function () use ($settlement, $reversedBy, $reasonAr): Settlement {
            $this->poster->reverse($settlement->journalEntry, $reversedBy, $reasonAr);

            $settlement->update([
                'status' => SettlementStatus::Reversed,
                'notes_ar' => trim(($settlement->notes_ar !== null ? $settlement->notes_ar."\n" : '').'سبب العكس: '.$reasonAr),
            ]);

            // استثناء موثق لقاعدة الانتقالات: التصفية المعكوسة تعيد الدفعة
            // إلى "منفذة" لتُصفى من جديد
            $cohort = $settlement->cohort;
            $cohort->forceFill(['status' => CohortStatus::Delivered])->save();

            return $settlement->refresh();
        });
    }

    /**
     * §8.5 journal produced on settlement.
     */
    protected function postSettlementJournal(Cohort $cohort, DistributionResult $result, int $createdBy): JournalEntry
    {
        $lines = [];
        $currentAccounts = $this->partnerCurrentAccounts();

        foreach ($result->delivererAllocations as $allocation) {
            if ($allocation['amount']->isZero()) {
                continue;
            }

            if ($allocation['type'] === 'partner') {
                $lines[] = [
                    'account_id' => Account::byCode('5010')->id,
                    'debit' => $allocation['amount'],
                    'partner_id' => $allocation['partner_id'],
                    'cohort_id' => $cohort->id,
                    'memo_ar' => 'حصة الشريك المنفذ — '.$allocation['name'],
                ];
                $lines[] = [
                    'account_id' => $currentAccounts[$allocation['partner_id']],
                    'credit' => $allocation['amount'],
                    'partner_id' => $allocation['partner_id'],
                    'memo_ar' => 'استحقاق تنفيذ — '.$allocation['name'],
                ];
            } else {
                $lines[] = [
                    'account_id' => Account::byCode('5020')->id,
                    'debit' => $allocation['amount'],
                    'cohort_id' => $cohort->id,
                    'memo_ar' => 'أتعاب مدرب خارجي — '.$allocation['name'],
                ];
                $lines[] = [
                    'account_id' => Account::byCode('2020')->id,
                    'credit' => $allocation['amount'],
                    'memo_ar' => 'مستحق للمدرب — '.$allocation['name'],
                ];
            }
        }

        foreach ($result->centerAllocations as $allocation) {
            if ($allocation['amount']->isZero()) {
                continue;
            }

            if ($allocation['amount']->isPositive()) {
                $lines[] = [
                    'account_id' => Account::byCode('3090')->id,
                    'debit' => $allocation['amount'],
                    'memo_ar' => 'توزيع حصة المركز',
                ];
                $lines[] = [
                    'account_id' => $currentAccounts[$allocation['partner_id']],
                    'credit' => $allocation['amount'],
                    'partner_id' => $allocation['partner_id'],
                    'memo_ar' => 'حصة من أرباح المركز — '.$allocation['name'],
                ];
            } else {
                // خسارة: تُحمَّل على الحساب الجاري للشريك
                $absolute = new Money(-$allocation['amount']->baisa);
                $lines[] = [
                    'account_id' => $currentAccounts[$allocation['partner_id']],
                    'debit' => $absolute,
                    'partner_id' => $allocation['partner_id'],
                    'memo_ar' => 'نصيب من خسارة الدفعة — '.$allocation['name'],
                ];
                $lines[] = [
                    'account_id' => Account::byCode('3090')->id,
                    'credit' => $absolute,
                    'memo_ar' => 'تحميل خسارة على الشركاء',
                ];
            }
        }

        if ($lines === []) {
            throw new DomainException('Settlement produced no journal lines — nothing to distribute.');
        }

        return $this->poster->post(
            entryDate: now(),
            descriptionAr: 'تصفية الدفعة '.$cohort->code,
            lines: $lines,
            createdBy: $createdBy,
            cohortId: $cohort->id,
            invoicingEntityId: $cohort->invoicing_entity_id,
            referenceType: 'settlement',
            referenceId: $cohort->id,
        );
    }

    /**
     * @return array<int, int> partner_id => current-account id
     */
    protected function partnerCurrentAccounts(): array
    {
        return Account::query()
            ->where('code', 'like', '302%')
            ->whereNotNull('partner_id')
            ->pluck('id', 'partner_id')
            ->all();
    }

    /**
     * @return array<string, int>
     */
    protected function amountColumns(DistributionResult $result): array
    {
        return [
            'gross_revenue_baisa' => $result->grossRevenue->baisa,
            'direct_costs_baisa' => $result->directCosts->baisa,
            'net_distributable_baisa' => $result->netDistributable->baisa,
            'deliverer_total_baisa' => $result->delivererTotal->baisa,
            'center_share_baisa' => $result->centerShare->baisa,
            'center_opex_allocated_baisa' => 0,
            'distributable_profit_baisa' => $result->centerShare->baisa,
        ];
    }

    protected function assertDraft(Settlement $settlement): void
    {
        if ($settlement->status !== SettlementStatus::Draft) {
            throw new DomainException('Settlement is no longer a draft.');
        }
    }

    protected function nextNumber(): string
    {
        $year = now()->year;

        foreach (range(1, 3) as $attempt) {
            $last = Settlement::query()
                ->where('settlement_number', 'like', "ST-{$year}-%")
                ->orderByDesc('settlement_number')
                ->value('settlement_number');

            $next = $last !== null ? ((int) substr($last, -4)) + 1 : 1;
            $candidate = sprintf('ST-%d-%04d', $year, $next);

            if (! Settlement::query()->where('settlement_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new DomainException('Could not allocate a settlement number.');
    }
}
