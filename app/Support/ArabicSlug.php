<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Spec Phase 2: slugs from Arabic titles — transliterate to clean ASCII,
 * guarantee uniqueness (including soft-deleted rows, since the unique index
 * covers them).
 */
class ArabicSlug
{
    public static function generate(string $title, string $table, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($title, '-', 'ar');

        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $suffix = 2;

        while (self::exists($table, $column, $candidate, $ignoreId)) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    protected static function exists(string $table, string $column, string $candidate, ?int $ignoreId): bool
    {
        return DB::table($table)
            ->where($column, $candidate)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
