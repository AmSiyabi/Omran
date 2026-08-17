<?php

namespace App\Observers;

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use DomainException;

/**
 * Spec §8.3 guard: a posted entry is never edited or deleted. The single
 * permitted mutation is flipping status posted→reversed while linking the
 * reversing entry — anything else throws.
 */
class JournalEntryObserver
{
    public function updating(JournalEntry $entry): void
    {
        $dirty = array_keys($entry->getDirty());
        $allowed = ['status', 'reversed_by_entry_id'];

        $isReversalFlip = $dirty !== []
            && array_diff($dirty, $allowed) === []
            && $entry->getOriginal('status') === JournalEntryStatus::Posted
            && $entry->status === JournalEntryStatus::Reversed;

        if (! $isReversalFlip) {
            throw new DomainException(
                'Journal entries are append-only — corrections happen through reversing entries (spec §8.3).'
            );
        }
    }

    public function deleting(JournalEntry $entry): void
    {
        throw new DomainException(
            'Journal entries are append-only and can never be deleted (spec §8.3).'
        );
    }
}
