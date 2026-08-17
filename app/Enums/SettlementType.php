<?php

namespace App\Enums;

enum SettlementType: string
{
    case Cohort = 'cohort';
    case Monthly = 'monthly';

    public function label(): string
    {
        return __('finance.settlement_type.'.$this->value);
    }
}
