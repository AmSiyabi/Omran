<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * String ⇄ integer baisa conversion for form input/output. 1 OMR = 1000
 * baisa; pure string parsing — floats never touch a money value (spec §8.1).
 * The full Money value object arrives in Phase 5 and builds on this.
 */
class Baisa
{
    public const INPUT_PATTERN = '/^\d{1,9}(\.\d{1,3})?$/';

    public static function fromString(string $omr): int
    {
        $omr = trim($omr);

        if (! preg_match(self::INPUT_PATTERN, $omr)) {
            throw new InvalidArgumentException("Not a valid OMR amount: {$omr}");
        }

        [$rials, $baisa] = array_pad(explode('.', $omr, 2), 2, '0');

        return ((int) $rials) * 1000 + (int) str_pad($baisa, 3, '0');
    }

    /**
     * 640500 → "640.500" — three decimals always (spec §6.3).
     */
    public static function toString(int $baisa): string
    {
        $sign = $baisa < 0 ? '-' : '';
        $abs = abs($baisa);

        return sprintf('%s%d.%03d', $sign, intdiv($abs, 1000), $abs % 1000);
    }

    /**
     * "640.500 ر.ع." — the only currency display format.
     */
    public static function format(int $baisa): string
    {
        return self::toString($baisa).' '.__('common.omr');
    }
}
