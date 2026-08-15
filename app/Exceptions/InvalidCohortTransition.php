<?php

namespace App\Exceptions;

use App\Enums\CohortStatus;
use DomainException;

class InvalidCohortTransition extends DomainException
{
    public static function between(CohortStatus $from, CohortStatus $to): self
    {
        return new self(
            "Cohort status cannot move from {$from->value} to {$to->value}."
        );
    }
}
