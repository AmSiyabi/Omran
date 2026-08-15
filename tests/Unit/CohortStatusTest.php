<?php

use App\Enums\CohortStatus;

/**
 * Phase 2 acceptance: the full transition matrix, valid and invalid.
 */
test('the transition matrix allows exactly the specced moves', function () {
    $expectations = [
        'draft' => ['announced', 'cancelled'],
        'announced' => ['open', 'cancelled'],
        'open' => ['closed', 'cancelled'],
        'closed' => ['delivered', 'cancelled'],
        'delivered' => ['settled', 'cancelled'],
        'settled' => [],
        'cancelled' => [],
    ];

    foreach (CohortStatus::cases() as $from) {
        foreach (CohortStatus::cases() as $to) {
            $allowed = in_array($to->value, $expectations[$from->value], true);

            expect($from->canTransitionTo($to))->toBe(
                $allowed,
                "{$from->value} → {$to->value} should be ".($allowed ? 'allowed' : 'forbidden'),
            );
        }
    }
});
