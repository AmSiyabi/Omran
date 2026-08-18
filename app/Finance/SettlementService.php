<?php

namespace App\Finance;

use App\Enums\CohortStatus;
use App\Enums\SettlementStatus;
use App\Enums\SettlementType;
use App\Models\Account;
use App\Models\Cohort;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Partner;
use App\Models\Setting;
use App\Models\Settlement;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Carbon;
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
     * التصفية الشهرية (§8.6, D-040): كل الدفعات المنفذة غير المصفاة في
     * الشهر تُحسب دفعة دفعة، وتُخصم مصروفات الفترة التشغيلية من وعاء
     * المركز قبل القسمة (حسب الإعداد) — ويظهر الرقمان جنباً إلى جنب.
     */
    public function computeMonthlyDraft(int $year, int $month): Settlement
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $cohorts = Cohort::query()
            ->where('status', CohortStatus::Delivered)
            ->whereBetween('ends_at', [$periodStart, $periodEnd])
            ->whereDoesntHave('settlements', fn ($query) => $query->whereIn('status', ['draft', 'posted']))
            ->orderBy('ends_at')
            ->get();

        if ($cohorts->isEmpty()) {
            throw new DomainException('لا توجد دفعات منفذة غير مصفاة في هذا الشهر.');
        }

        $perCohort = [];
        $flags = [];
        $gross = 0;
        $directCosts = 0;
        $net = 0;
        $delivererTotal = 0;
        $centerTotal = 0;

        foreach ($cohorts as $cohort) {
            $result = $this->engine->compute($cohort);

            $perCohort[] = ['cohort_id' => $cohort->id, 'code' => $cohort->code] + $result->toSnapshot();
            $flags = array_values(array_unique([...$flags, ...$result->flags]));
            $gross += $result->grossRevenue->baisa;
            $directCosts += $result->directCosts->baisa;
            $net += $result->netDistributable->baisa;
            $delivererTotal += $result->delivererTotal->baisa;
            $centerTotal += $result->centerShare->baisa;
        }

        $opex = $this->opexChargedToPool() ? $this->periodOpex($periodStart, $periodEnd) : 0;
        $distributable = $centerTotal - $opex;

        if ($distributable < 0 && ! in_array('LOSS', $flags, true)) {
            $flags[] = 'LOSS';
        }

        return Settlement::query()->create([
            'settlement_number' => $this->nextNumber(),
            'type' => SettlementType::Monthly,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'gross_revenue_baisa' => $gross,
            'direct_costs_baisa' => $directCosts,
            'net_distributable_baisa' => $net,
            'deliverer_total_baisa' => $delivererTotal,
            'center_share_baisa' => $centerTotal,
            'center_opex_allocated_baisa' => $opex,
            'distributable_profit_baisa' => $distributable,
            'status' => SettlementStatus::Draft,
            'computed_at' => now(),
            'snapshot' => [
                'type' => 'monthly',
                'period' => [$periodStart->toDateString(), $periodEnd->toDateString()],
                'cohorts' => $perCohort,
                'center_share_total_baisa' => $centerTotal,
                'opex_baisa' => $opex,
                'distributable_baisa' => $distributable,
                'opex_charged_to_center_pool' => $this->opexChargedToPool(),
                'center_allocations' => $this->allocateDistributable($distributable),
                'flags' => $flags,
            ],
        ]);
    }

    public function confirmMonthly(Settlement $settlement, int $confirmedBy, bool $acceptLoss = false, ?string $overrideReasonAr = null): Settlement
    {
        $this->assertDraft($settlement);

        if ($settlement->type !== SettlementType::Monthly) {
            throw new DomainException('Not a monthly settlement.');
        }

        return DB::transaction(function () use ($settlement, $confirmedBy, $acceptLoss, $overrideReasonAr): Settlement {
            $snapshot = $settlement->snapshot;
            $flags = $snapshot['flags'] ?? [];

            if (in_array('LOSS', $flags, true) && ! $acceptLoss) {
                throw new DomainException('LOSS');
            }

            if (in_array('OVERCOMMITTED', $flags, true) && ($overrideReasonAr === null || trim($overrideReasonAr) === '')) {
                throw new DomainException('OVERCOMMITTED');
            }

            $lines = [];
            $currentAccounts = $this->partnerCurrentAccounts();

            // استحقاقات المنفذين لكل دفعة
            foreach ($snapshot['cohorts'] as $cohortResult) {
                foreach ($cohortResult['deliverer_allocations'] as $allocation) {
                    if ($allocation['amount_baisa'] === 0) {
                        continue;
                    }

                    $amount = new Money($allocation['amount_baisa']);

                    if ($allocation['type'] === 'partner') {
                        $lines[] = ['account_id' => Account::byCode('5010')->id, 'debit' => $amount, 'partner_id' => $allocation['partner_id'], 'cohort_id' => $cohortResult['cohort_id'], 'memo_ar' => 'حصة الشريك المنفذ — '.$allocation['name'].' ('.$cohortResult['code'].')'];
                        $lines[] = ['account_id' => $currentAccounts[$allocation['partner_id']], 'credit' => $amount, 'partner_id' => $allocation['partner_id'], 'memo_ar' => 'استحقاق تنفيذ — '.$cohortResult['code']];
                    } else {
                        $lines[] = ['account_id' => Account::byCode('5020')->id, 'debit' => $amount, 'cohort_id' => $cohortResult['cohort_id'], 'memo_ar' => 'أتعاب مدرب خارجي — '.$allocation['name']];
                        $lines[] = ['account_id' => Account::byCode('2020')->id, 'credit' => $amount, 'memo_ar' => 'مستحق للمدرب — '.$allocation['name']];
                    }
                }
            }

            // توزيع الوعاء الصافي (حصة المركز − مصروفات الفترة) على الشريكين
            foreach ($snapshot['center_allocations'] as $allocation) {
                if ($allocation['amount_baisa'] === 0) {
                    continue;
                }

                $amount = new Money(abs($allocation['amount_baisa']));

                if ($allocation['amount_baisa'] > 0) {
                    $lines[] = ['account_id' => Account::byCode('3090')->id, 'debit' => $amount, 'memo_ar' => 'توزيع أرباح الفترة'];
                    $lines[] = ['account_id' => $currentAccounts[$allocation['partner_id']], 'credit' => $amount, 'partner_id' => $allocation['partner_id'], 'memo_ar' => 'حصة من أرباح الفترة — '.$allocation['name']];
                } else {
                    $lines[] = ['account_id' => $currentAccounts[$allocation['partner_id']], 'debit' => $amount, 'partner_id' => $allocation['partner_id'], 'memo_ar' => 'نصيب من خسارة الفترة — '.$allocation['name']];
                    $lines[] = ['account_id' => Account::byCode('3090')->id, 'credit' => $amount, 'memo_ar' => 'تحميل خسارة الفترة'];
                }
            }

            if ($lines === []) {
                throw new DomainException('Monthly settlement produced no journal lines.');
            }

            $entry = $this->poster->post(
                entryDate: now(),
                descriptionAr: 'تصفية شهرية — '.$settlement->period_start->translatedFormat('F Y'),
                lines: $lines,
                createdBy: $confirmedBy,
                referenceType: 'settlement',
                referenceId: $settlement->id,
            );

            foreach ($snapshot['cohorts'] as $cohortResult) {
                Cohort::query()->findOrFail($cohortResult['cohort_id'])->transitionTo(CohortStatus::Settled);
            }

            $settlement->update([
                'status' => SettlementStatus::Posted,
                'journal_entry_id' => $entry->id,
                'confirmed_by' => $confirmedBy,
                'confirmed_at' => now(),
                'notes_ar' => $overrideReasonAr !== null && trim($overrideReasonAr) !== ''
                    ? 'سبب التجاوز: '.trim($overrideReasonAr)
                    : $settlement->notes_ar,
                'snapshot' => $snapshot + [
                    'confirmed_at' => now()->toIso8601String(),
                    'journal_entry_number' => $entry->entry_number,
                    'accepted_loss' => $acceptLoss,
                    'override_reason' => $overrideReasonAr,
                ],
            ]);

            return $settlement->refresh();
        });
    }

    protected function opexChargedToPool(): bool
    {
        return (bool) Setting::get('opex_charged_to_center_pool', true);
    }

    protected function periodOpex(CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('accounts.code', 'like', '6%')
            ->whereBetween('journal_entries.entry_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(journal_lines.debit_baisa - journal_lines.credit_baisa), 0) as total')
            ->toBase()
            ->value('total');
    }

    /**
     * @return list<array{partner_id: int, name: string, ownership: string, amount_baisa: int}>
     */
    protected function allocateDistributable(int $distributable): array
    {
        $partners = Partner::query()->where('is_active', true)->orderBy('id')->get();

        if ($partners->isEmpty()) {
            throw new DomainException('No active partners.');
        }

        if ($distributable === 0) {
            return $partners->map(fn ($partner) => [
                'partner_id' => $partner->id,
                'name' => $partner->display_name_ar,
                'ownership' => (string) $partner->ownership_percent,
                'amount_baisa' => 0,
            ])->values()->all();
        }

        $weights = $partners->mapWithKeys(fn ($partner) => [$partner->id => (string) $partner->ownership_percent])->all();
        $shares = (new Money($distributable))->allocate($weights);

        return $partners->map(fn ($partner) => [
            'partner_id' => $partner->id,
            'name' => $partner->display_name_ar,
            'ownership' => (string) $partner->ownership_percent,
            'amount_baisa' => $shares[$partner->id]->baisa,
        ])->values()->all();
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
            // (أو دفعات الشهر كلها) إلى "منفذة" لتُصفى من جديد
            if ($settlement->type === SettlementType::Monthly) {
                foreach ($settlement->snapshot['cohorts'] ?? [] as $cohortResult) {
                    Cohort::query()->whereKey($cohortResult['cohort_id'])->first()
                        ?->forceFill(['status' => CohortStatus::Delivered])->save();
                }
            } else {
                $settlement->cohort?->forceFill(['status' => CohortStatus::Delivered])->save();
            }

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
