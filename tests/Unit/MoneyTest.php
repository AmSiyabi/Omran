<?php

use App\Finance\Money;

test('arithmetic is immutable integer baisa', function () {
    $a = new Money(640500);
    $b = new Money(100250);

    expect($a->add($b)->baisa)->toBe(740750)
        ->and($a->subtract($b)->baisa)->toBe(540250)
        ->and($a->baisa)->toBe(640500)
        ->and($a->format())->toBe('640.500 ر.ع.')
        ->and((string) $a)->toBe('640.500')
        ->and(Money::fromString('640.500')->baisa)->toBe(640500);
});

test('predicates behave', function () {
    expect((new Money(-5))->isNegative())->toBeTrue()
        ->and(Money::zero()->isZero())->toBeTrue()
        ->and((new Money(1))->isPositive())->toBeTrue()
        ->and((new Money(5))->equals(new Money(5)))->toBeTrue();
});

test('multiplyByPercent floors to whole baisa without floats', function () {
    expect((new Money(640000))->multiplyByPercent(80)->baisa)->toBe(512000)
        ->and((new Money(850000))->multiplyByPercent(70)->baisa)->toBe(595000)
        ->and((new Money(333333))->multiplyByPercent(70)->baisa)->toBe(233333)
        ->and((new Money(100))->multiplyByPercent('33.33')->baisa)->toBe(33);
});

test('allocate splits exactly with largest remainder — spec §8.7', function () {
    $shares = (new Money(333333))->allocate(['deliverer' => 70, 'center' => 30]);

    expect($shares['deliverer']->baisa)->toBe(233333)
        ->and($shares['center']->baisa)->toBe(100000)
        ->and($shares['deliverer']->baisa + $shares['center']->baisa)->toBe(333333);
});

test('allocate handles indivisible amounts to the last baisa', function () {
    $shares = (new Money(100))->allocate(['a' => 1, 'b' => 1, 'c' => 1]);

    expect(array_sum(array_map(fn (Money $m) => $m->baisa, $shares)))->toBe(100)
        ->and(collect($shares)->map(fn (Money $m) => $m->baisa)->sort()->values()->all())->toBe([33, 33, 34]);
});

test('allocate negates losses symmetrically', function () {
    $shares = (new Money(-100000))->allocate(['hamad' => 50, 'ammar' => 50]);

    expect($shares['hamad']->baisa)->toBe(-50000)
        ->and($shares['ammar']->baisa)->toBe(-50000);

    $odd = (new Money(-101))->allocate(['a' => 50, 'b' => 50]);

    expect($odd['a']->baisa + $odd['b']->baisa)->toBe(-101);
});

test('allocate supports fractional weights', function () {
    $shares = (new Money(1000000))->allocate(['a' => '33.33', 'b' => '66.67']);

    expect($shares['a']->baisa + $shares['b']->baisa)->toBe(1000000)
        ->and($shares['a']->baisa)->toBe(333300);
});

test('allocate rejects empty, all-zero, and negative weights', function () {
    expect(fn () => (new Money(100))->allocate([]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => (new Money(100))->allocate(['a' => 0, 'b' => 0]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => (new Money(100))->allocate(['a' => -10, 'b' => 110]))->toThrow(InvalidArgumentException::class);
});

test('allocation property: random totals and weights always sum exactly', function () {
    mt_srand(20260817);

    foreach (range(1, 2000) as $i) {
        $total = new Money(mt_rand(0, 5_000_000));
        $weightCount = mt_rand(1, 5);
        $weights = [];

        foreach (range(1, $weightCount) as $w) {
            $weights[$w] = mt_rand(1, 10000) / 100;
        }

        $shares = $total->allocate($weights);
        $sum = array_sum(array_map(fn (Money $m) => $m->baisa, $shares));

        expect($sum)->toBe($total->baisa);
    }
});
