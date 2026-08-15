<?php

use App\Support\Baisa;

test('parses OMR strings to integer baisa without floats', function (string $input, int $expected) {
    expect(Baisa::fromString($input))->toBe($expected);
})->with([
    ['640.500', 640500],
    ['640.5', 640500],
    ['640.05', 640050],
    ['640.005', 640005],
    ['640', 640000],
    ['0.001', 1],
    ['0', 0],
    ['45.500', 45500],
]);

test('rejects malformed amounts', function (string $input) {
    Baisa::fromString($input);
})->with(['1.2345', '-5', 'abc', '1,5', '', '1.‏٥'])->throws(InvalidArgumentException::class);

test('formats baisa with three decimals always', function () {
    expect(Baisa::toString(640500))->toBe('640.500')
        ->and(Baisa::toString(45000))->toBe('45.000')
        ->and(Baisa::toString(5))->toBe('0.005')
        ->and(Baisa::toString(0))->toBe('0.000')
        ->and(Baisa::toString(-1250))->toBe('-1.250');
});
