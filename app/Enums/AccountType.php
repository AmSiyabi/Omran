<?php

namespace App\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case DirectCost = 'direct_cost';
    case OperatingExpense = 'operating_expense';

    public function label(): string
    {
        return __('finance.account_type.'.$this->value);
    }

    /**
     * Debit-normal accounts increase with debits.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this, [self::Asset, self::DirectCost, self::OperatingExpense], true);
    }
}
