<?php

namespace App\Enums;

enum SettlementStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __('finance.settlement_status.'.$this->value);
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Confirmed => 'info',
            self::Posted => 'success',
            self::Reversed => 'error',
        };
    }
}
