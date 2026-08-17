<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return __('courses.attendance_status.'.$this->value);
    }

    /**
     * Tap-cycle order for the classroom sheet.
     */
    public function next(): self
    {
        return match ($this) {
            self::Present => self::Absent,
            self::Absent => self::Late,
            self::Late => self::Excused,
            self::Excused => self::Present,
        };
    }
}
