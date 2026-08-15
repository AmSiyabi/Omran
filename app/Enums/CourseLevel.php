<?php

namespace App\Enums;

enum CourseLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case All = 'all';

    public function label(): string
    {
        return __('courses.level.'.$this->value);
    }
}
