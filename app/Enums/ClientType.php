<?php

namespace App\Enums;

enum ClientType: string
{
    case Government = 'government';
    case Private = 'private';
    case Ngo = 'ngo';
    case Individual = 'individual';

    public function label(): string
    {
        return __('courses.client_type.'.$this->value);
    }
}
