<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Confirmed = 'confirmed';
    case Waitlisted = 'waitlisted';
    case Cancelled = 'cancelled';
    case Attended = 'attended';
    case NoShow = 'no_show';

    public function label(): string
    {
        return __('courses.enrollment_status.'.$this->value);
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Confirmed => 'success',
            self::Waitlisted => 'neutral',
            self::Cancelled => 'error',
            self::Attended => 'gold',
            self::NoShow => 'error',
        };
    }

    /**
     * Statuses that occupy a seat in the cohort capacity.
     */
    public function holdsSeat(): bool
    {
        return in_array($this, [self::Confirmed, self::Attended, self::NoShow], true);
    }
}
