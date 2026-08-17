<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Waived = 'waived';

    public function label(): string
    {
        return __('courses.payment_status.'.$this->value);
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Unpaid => 'error',
            self::Partial => 'warning',
            self::Paid => 'success',
            self::Waived => 'neutral',
        };
    }
}
