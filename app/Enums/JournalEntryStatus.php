<?php

namespace App\Enums;

enum JournalEntryStatus: string
{
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __('finance.entry_status.'.$this->value);
    }
}
