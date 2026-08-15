<?php

namespace App\Enums;

enum DeliveryMode: string
{
    case Onsite = 'onsite';
    case Online = 'online';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return __('courses.delivery_mode.'.$this->value);
    }
}
