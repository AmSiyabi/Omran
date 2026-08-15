<?php

namespace App\Enums;

/**
 * Spec Phase 2: draft → announced → open → closed → delivered → settled,
 * plus cancelled from any state before settled. Nothing else.
 */
enum CohortStatus: string
{
    case Draft = 'draft';
    case Announced = 'announced';
    case Open = 'open';
    case Closed = 'closed';
    case Delivered = 'delivered';
    case Settled = 'settled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('courses.cohort_status.'.$this->value);
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Announced, self::Cancelled],
            self::Announced => [self::Open, self::Cancelled],
            self::Open => [self::Closed, self::Cancelled],
            self::Closed => [self::Delivered, self::Cancelled],
            self::Delivered => [self::Settled, self::Cancelled],
            self::Settled, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Announced => 'info',
            self::Open => 'success',
            self::Closed => 'warning',
            self::Delivered => 'gold',
            self::Settled => 'gold',
            self::Cancelled => 'error',
        };
    }
}
