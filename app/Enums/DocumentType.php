<?php

namespace App\Enums;

enum DocumentType: string
{
    case Receipt = 'receipt';
    case Invoice = 'invoice';
    case Contract = 'contract';
    case Quote = 'quote';
    case Other = 'other';

    public function label(): string
    {
        return __('finance.document_type.'.$this->value);
    }
}
