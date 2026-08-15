<?php

namespace App\Observers;

use App\Enums\DelivererType;
use App\Models\CohortDeliverer;
use DomainException;

/**
 * Spec §7.2 constraint: exactly one of partner_id / instructor_id is
 * non-null, matching deliverer_type. Mirrors the MySQL CHECK constraint.
 */
class CohortDelivererObserver
{
    public function saving(CohortDeliverer $deliverer): void
    {
        $validPartner = $deliverer->deliverer_type === DelivererType::Partner
            && $deliverer->partner_id !== null
            && $deliverer->instructor_id === null;

        $validExternal = $deliverer->deliverer_type === DelivererType::External
            && $deliverer->instructor_id !== null
            && $deliverer->partner_id === null;

        if (! $validPartner && ! $validExternal) {
            throw new DomainException(
                'A cohort deliverer must reference exactly one of partner or instructor, matching its type.'
            );
        }
    }
}
