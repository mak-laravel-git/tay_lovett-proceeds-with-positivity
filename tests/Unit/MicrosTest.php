<?php

use App\Support\Micros;

test('provider decimals become exact integer micros', function (string|int|float $input, int $expected) {
    expect(Micros::fromDecimal($input))->toBe($expected);
})->with([
    'cents string' => ['1.32', 1_320_000],
    'whole string' => ['4', 4_000_000],
    'integer' => [7, 7_000_000],
    'float' => [0.5, 500_000],
    'zero' => ['0', 0],
    'six decimals' => ['0.000001', 1],
    'rounds half up at the seventh decimal' => ['0.0000005', 1],
    'rounds down below half' => ['0.0000004', 0],
    'large' => ['12345.67', 12_345_670_000],
    // The classic float trap: 0.1 + 0.2 must not drift.
    'float noise' => [0.1 + 0.2, 300_000],
]);

test('null and empty are zero', function () {
    expect(Micros::fromDecimal(null))->toBe(0)->and(Micros::fromDecimal(''))->toBe(0);
});

test('anything that is not a plain non-negative decimal is rejected', function (string $input) {
    expect(fn () => Micros::fromDecimal($input))->toThrow(InvalidArgumentException::class);
})->with(['-1.00', 'abc', '1,000.00', '1e3', '$5', '1.2.3', ' ']);

test('micros format as two-decimal dollars, rounding half up', function (int $micros, string $expected) {
    expect(Micros::toDollars($micros))->toBe($expected);
})->with([
    [1_320_000, '1.32'],
    [0, '0.00'],
    [5_000, '0.01'],
    [4_999, '0.00'],
    [-600_000, '-0.60'],
    [125_500_000, '125.50'],
    [1_234_567_890, '1234.57'],
]);

test('a sum of many amounts never drifts the way floats do', function () {
    $total = 0;

    foreach (range(1, 1000) as $_) {
        $total += Micros::fromDecimal('0.10');
    }

    expect($total)->toBe(100_000_000)->and(Micros::toDollars($total))->toBe('100.00');
});
