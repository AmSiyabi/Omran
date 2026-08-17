<?php

namespace App\Finance;

use App\Support\Baisa;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable money value — an integer amount of baisa (1 OMR = 1000 baisa).
 *
 * Spec §8.1: money is never a float. All arithmetic on money values happens
 * inside this class and nowhere else; percentage splits go through
 * allocate() (largest-remainder, §8.7) so no baisa is ever lost or invented.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    public function __construct(
        public int $baisa,
    ) {}

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromString(string $omr): self
    {
        return new self(Baisa::fromString($omr));
    }

    public function add(self $other): self
    {
        return new self($this->baisa + $other->baisa);
    }

    public function subtract(self $other): self
    {
        return new self($this->baisa - $other->baisa);
    }

    /**
     * Percentage of the amount, floored to whole baisa. The remainder logic
     * that makes shares sum exactly lives in allocate() — use that for
     * splits; use this only when a single share is needed.
     *
     * @param  numeric-string|float|int  $percent
     */
    public function multiplyByPercent(float|int|string $percent): self
    {
        // عبر المئويات الصحيحة (بيسة لكل عشرة آلاف) لتفادي الفاصلة العائمة
        $basisPoints = (int) round(((float) $percent) * 100);

        return new self(intdiv($this->baisa * $basisPoints, 10000));
    }

    /**
     * Largest-remainder allocation (spec §8.7): shares always sum exactly
     * to the total. Negative totals allocate the absolute value and negate.
     *
     * @param  array<array-key, float|int|string>  $weights
     * @return array<array-key, self>
     */
    public function allocate(array $weights): array
    {
        if ($weights === []) {
            throw new InvalidArgumentException('Cannot allocate to an empty weight set.');
        }

        if ($this->baisa < 0) {
            $positive = (new self(-$this->baisa))->allocate($weights);

            return array_map(fn (self $share) => new self(-$share->baisa), $positive);
        }

        // الأوزان تصبح أعداداً صحيحة (بدقة جزء من مئة) قبل أي حساب
        $integerWeights = [];

        foreach ($weights as $key => $weight) {
            $integerWeight = (int) round(((float) $weight) * 100);

            if ($integerWeight < 0) {
                throw new InvalidArgumentException('Allocation weights must not be negative.');
            }

            $integerWeights[$key] = $integerWeight;
        }

        $sum = array_sum($integerWeights);

        if ($sum === 0) {
            throw new InvalidArgumentException('Allocation weights must not all be zero.');
        }

        $shares = [];
        $remainders = [];
        $allocated = 0;

        foreach ($integerWeights as $key => $weight) {
            $numerator = $this->baisa * $weight;
            $floor = intdiv($numerator, $sum);

            $shares[$key] = $floor;
            $remainders[$key] = $numerator % $sum;
            $allocated += $floor;
        }

        $leftover = $this->baisa - $allocated;
        arsort($remainders);

        foreach (array_keys($remainders) as $key) {
            if ($leftover <= 0) {
                break;
            }

            $shares[$key]++;
            $leftover--;
        }

        return array_map(fn (int $share) => new self($share), $shares);
    }

    public function isNegative(): bool
    {
        return $this->baisa < 0;
    }

    public function isZero(): bool
    {
        return $this->baisa === 0;
    }

    public function isPositive(): bool
    {
        return $this->baisa > 0;
    }

    public function equals(self $other): bool
    {
        return $this->baisa === $other->baisa;
    }

    /**
     * "640.500 ر.ع." — three decimals always (spec §6.3).
     */
    public function format(): string
    {
        return Baisa::format($this->baisa);
    }

    public function toDecimalString(): string
    {
        return Baisa::toString($this->baisa);
    }

    public function __toString(): string
    {
        return $this->toDecimalString();
    }

    public function jsonSerialize(): int
    {
        return $this->baisa;
    }
}
