<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The registration link exists but cannot accept registrations right now.
 * `reason` maps to a friendly Arabic message on the public page.
 */
class LinkNotUsable extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
    ) {
        parent::__construct("Registration link is not usable: {$reason}");
    }

    public static function inactive(): self
    {
        return new self('inactive');
    }

    public static function expired(): self
    {
        return new self('expired');
    }

    public static function exhausted(): self
    {
        return new self('exhausted');
    }

    public static function registrationClosed(): self
    {
        return new self('closed');
    }
}
