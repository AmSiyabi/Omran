<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Stores a bare Y-m-d string. Laravel's built-in `date` cast serializes
 * through the model's dateFormat (Y-m-d H:i:s), which corrupts DATE-column
 * comparisons on drivers without a real DATE type (sqlite): a row written
 * "today" sorts after the string bound "Y-m-d" and falls out of every
 * whereBetween. MySQL truncates on insert, so only tests ever saw it.
 *
 * @implements CastsAttributes<Carbon, string|\DateTimeInterface|null>
 */
class DateOnlyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse((string) $value)->startOfDay();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse((string) $value)->format('Y-m-d');
    }
}
