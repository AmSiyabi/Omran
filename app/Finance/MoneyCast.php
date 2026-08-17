<?php

namespace App\Finance;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * BIGINT baisa column ⇄ Money value object (spec §8.1).
 *
 * @implements CastsAttributes<Money, mixed>
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return new Money((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->baisa;
        }

        if (is_int($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            "Attribute {$key} accepts only Money or int baisa — never floats or strings (spec §8.1)."
        );
    }
}
