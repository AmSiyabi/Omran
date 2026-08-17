<?php

namespace App\Observers;

use App\Models\JournalLine;
use DomainException;

/**
 * Spec §8.3 guard: journal lines are strictly immutable once created.
 */
class JournalLineObserver
{
    public function updating(JournalLine $line): void
    {
        throw new DomainException(
            'Journal lines are append-only and can never be updated (spec §8.3).'
        );
    }

    public function deleting(JournalLine $line): void
    {
        throw new DomainException(
            'Journal lines are append-only and can never be deleted (spec §8.3).'
        );
    }
}
