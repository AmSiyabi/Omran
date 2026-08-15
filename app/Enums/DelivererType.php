<?php

namespace App\Enums;

enum DelivererType: string
{
    case Partner = 'partner';
    case External = 'external';

    public function label(): string
    {
        return __('courses.deliverer_type.'.$this->value);
    }
}
