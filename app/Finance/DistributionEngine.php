<?php

namespace App\Finance;

use App\Enums\DelivererType;
use App\Models\Cohort;
use App\Models\DistributionPolicy;
use App\Models\JournalLine;
use App\Models\Partner;
use DomainException;

/**
 * المادة الثانية من عقد الشراكة، ككود (spec §8.5).
 *
 * Pure and deterministic: reads the journal and the cohort, writes nothing.
 * Every split goes through Money::allocate (largest remainder, §8.7) so the
 * allocations always sum to net_distributable exactly — to the baisa.
 */
class DistributionEngine
{
    public function compute(Cohort $cohort): DistributionResult
    {
        $cohort->loadMissing(['deliverers.partner', 'deliverers.instructor']);

        $policy = $cohort->distribution_policy_id !== null
            ? DistributionPolicy::query()->findOrFail($cohort->distribution_policy_id)
            : throw new DomainException('Cohort has no distribution policy assigned.');

        // 1. gross_revenue = صافي دائن حسابات 4xxx لهذه الدفعة
        $grossRevenue = new Money($this->sumForCohort($cohort->id, '4', creditNormal: true));

        // 2. direct_costs = مدين 5020–5090 (باستثناء 5010 — هو ناتج هذا الحساب)
        $directCosts = new Money($this->sumForCohort($cohort->id, '5', creditNormal: false, excludeCodes: ['5010']));

        // 3.
        $netDistributable = $grossRevenue->subtract($directCosts);

        $flags = [];
        $externalFee = null;

        // 4. خسارة: لا حصة للمنفذ، والخسارة على وعاء المركز، وتُحجب التصفية
        if (! $netDistributable->isPositive()) {
            $delivererTotal = Money::zero();
            $centerShare = $netDistributable;
            $flags[] = 'LOSS';
        } elseif ($policy->external_fee_mode === 'fixed') {
            // 5. أجر ثابت متفق عليه مسبقاً
            $externalFee = new Money((int) ($cohort->external_fee_baisa ?? 0));
            $delivererTotal = $externalFee;
            $centerShare = $netDistributable->subtract($externalFee);

            if ($externalFee->baisa > $netDistributable->baisa) {
                $flags[] = 'OVERCOMMITTED';
            }
        } else {
            // 5. نسبة مئوية عبر التوزيع الدقيق — لا كسور ضائعة
            $split = $netDistributable->allocate([
                'deliverer' => (string) $policy->deliverer_share_percent,
                'center' => (string) (100 - (float) $policy->deliverer_share_percent),
            ]);

            $delivererTotal = $split['deliverer'];
            $centerShare = $split['center'];
        }

        // 6. حصة المنفذين تُقسم بأوزانهم
        $delivererAllocations = $this->splitAcrossDeliverers($cohort, $delivererTotal);

        // 7. حصة المركز تُقسم على الشريكين النشطين بنسب الملكية
        $centerAllocations = $this->splitAcrossPartners($centerShare);

        $result = new DistributionResult(
            grossRevenue: $grossRevenue,
            directCosts: $directCosts,
            netDistributable: $netDistributable,
            delivererTotal: $delivererTotal,
            centerShare: $centerShare,
            delivererAllocations: $delivererAllocations,
            centerAllocations: $centerAllocations,
            flags: $flags,
            policy: [
                'code' => $policy->code,
                'name_ar' => $policy->name_ar,
                'deliverer_share_percent' => $policy->deliverer_share_percent !== null ? (string) $policy->deliverer_share_percent : null,
                'external_fee_mode' => $policy->external_fee_mode,
                'version' => $policy->version,
            ],
            externalFee: $externalFee,
        );

        // 8. الحصص مجتمعة تساوي الصافي — بالبيسة، دائماً
        if ($result->totalAllocated() !== $netDistributable->baisa) {
            throw new DomainException(
                "Distribution invariant violated: allocated {$result->totalAllocated()} ≠ net {$netDistributable->baisa}."
            );
        }

        return $result;
    }

    /**
     * @param  list<string>  $excludeCodes
     */
    protected function sumForCohort(int $cohortId, string $codePrefix, bool $creditNormal, array $excludeCodes = []): int
    {
        $query = JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.cohort_id', $cohortId)
            ->where('journal_entries.status', 'posted')
            ->where('accounts.code', 'like', $codePrefix.'%');

        foreach ($excludeCodes as $code) {
            $query->where('accounts.code', '!=', $code);
        }

        $sums = $query
            ->selectRaw('COALESCE(SUM(journal_lines.debit_baisa), 0) as debits, COALESCE(SUM(journal_lines.credit_baisa), 0) as credits')
            ->toBase()
            ->first();

        return $creditNormal
            ? (int) $sums->credits - (int) $sums->debits
            : (int) $sums->debits - (int) $sums->credits;
    }

    /**
     * @return list<array{type: string, partner_id: ?int, instructor_id: ?int, name: string, weight: string, amount: Money}>
     */
    protected function splitAcrossDeliverers(Cohort $cohort, Money $total): array
    {
        $deliverers = $cohort->deliverers;

        if ($deliverers->isEmpty()) {
            if (! $total->isZero()) {
                throw new DomainException('Cohort has a deliverer share but no deliverers assigned.');
            }

            return [];
        }

        if ($total->isZero()) {
            return [];
        }

        $weights = $deliverers->mapWithKeys(
            fn ($deliverer) => [$deliverer->id => (string) $deliverer->share_weight]
        )->all();

        $shares = $total->allocate($weights);

        return $deliverers->map(fn ($deliverer) => [
            'type' => $deliverer->deliverer_type->value,
            'partner_id' => $deliverer->partner_id,
            'instructor_id' => $deliverer->instructor_id,
            'name' => $deliverer->deliverer_type === DelivererType::Partner
                ? $deliverer->partner->display_name_ar
                : $deliverer->instructor->name_ar,
            'weight' => (string) $deliverer->share_weight,
            'amount' => $shares[$deliverer->id],
        ])->values()->all();
    }

    /**
     * @return list<array{partner_id: int, name: string, ownership: string, amount: Money}>
     */
    protected function splitAcrossPartners(Money $centerShare): array
    {
        $partners = Partner::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($partners->isEmpty()) {
            throw new DomainException('No active partners to receive the center share.');
        }

        if ($centerShare->isZero()) {
            return $partners->map(fn (Partner $partner) => [
                'partner_id' => $partner->id,
                'name' => $partner->display_name_ar,
                'ownership' => (string) $partner->ownership_percent,
                'amount' => Money::zero(),
            ])->values()->all();
        }

        $weights = $partners->mapWithKeys(
            fn (Partner $partner) => [$partner->id => (string) $partner->ownership_percent]
        )->all();

        $shares = $centerShare->allocate($weights);

        return $partners->map(fn (Partner $partner) => [
            'partner_id' => $partner->id,
            'name' => $partner->display_name_ar,
            'ownership' => (string) $partner->ownership_percent,
            'amount' => $shares[$partner->id],
        ])->values()->all();
    }
}
