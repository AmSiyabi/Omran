<?php

namespace App\Finance;

/**
 * The frozen outcome of one distribution computation. Everything a
 * settlement snapshot needs to explain itself years later.
 */
final readonly class DistributionResult
{
    /**
     * @param  list<array{type: string, partner_id: ?int, instructor_id: ?int, name: string, weight: string, amount: Money}>  $delivererAllocations
     * @param  list<array{partner_id: int, name: string, ownership: string, amount: Money}>  $centerAllocations
     * @param  list<string>  $flags  LOSS | OVERCOMMITTED
     * @param  array{code: string, name_ar: string, deliverer_share_percent: ?string, external_fee_mode: string, version: int}  $policy
     */
    public function __construct(
        public Money $grossRevenue,
        public Money $directCosts,
        public Money $netDistributable,
        public Money $delivererTotal,
        public Money $centerShare,
        public array $delivererAllocations,
        public array $centerAllocations,
        public array $flags,
        public array $policy,
        public ?Money $externalFee = null,
    ) {}

    public function totalAllocated(): int
    {
        $deliverers = array_sum(array_map(fn (array $a) => $a['amount']->baisa, $this->delivererAllocations));
        $center = array_sum(array_map(fn (array $a) => $a['amount']->baisa, $this->centerAllocations));

        return $deliverers + $center;
    }

    public function isBlocked(): bool
    {
        return $this->flags !== [];
    }

    /**
     * Serializable form for the settlement snapshot (spec §8.5).
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'gross_revenue_baisa' => $this->grossRevenue->baisa,
            'direct_costs_baisa' => $this->directCosts->baisa,
            'net_distributable_baisa' => $this->netDistributable->baisa,
            'deliverer_total_baisa' => $this->delivererTotal->baisa,
            'center_share_baisa' => $this->centerShare->baisa,
            'external_fee_baisa' => $this->externalFee?->baisa,
            'deliverer_allocations' => array_map(fn (array $a) => [
                'type' => $a['type'],
                'partner_id' => $a['partner_id'],
                'instructor_id' => $a['instructor_id'],
                'name' => $a['name'],
                'weight' => $a['weight'],
                'amount_baisa' => $a['amount']->baisa,
            ], $this->delivererAllocations),
            'center_allocations' => array_map(fn (array $a) => [
                'partner_id' => $a['partner_id'],
                'name' => $a['name'],
                'ownership' => $a['ownership'],
                'amount_baisa' => $a['amount']->baisa,
            ], $this->centerAllocations),
            'flags' => $this->flags,
            'policy' => $this->policy,
        ];
    }
}
