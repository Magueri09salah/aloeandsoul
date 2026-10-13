<?php

use App\Support\Money;

it('parses decimal input into minor units without floats', function (string $input, int $expected) {
    expect(Money::parse($input, 'MAD'))->toBe($expected);
})->with([
    ['249', 24900],
    ['249.5', 24950],
    ['249.50', 24950],
    ['249,50', 24950],
    ['1 200', 120000],
    ['0.01', 1],
    ['0.10', 10],
]);

it('rejects invalid amounts', function (string $input) {
    Money::parse($input, 'MAD');
})->throws(InvalidArgumentException::class)->with(['abc', '12.345', '-5', '1.2.3']);

it('treats empty input as no amount', function () {
    expect(Money::parse('', 'MAD'))->toBeNull()
        ->and(Money::parse(null, 'MAD'))->toBeNull();
});

it('respects the currency exponent', function () {
    expect(Money::parse('1500', 'JPY'))->toBe(1500)
        ->and(Money::parse('1.250', 'TND'))->toBe(1250)
        ->and(Money::exponent('MAD'))->toBe(2);
});

it('formats minor units back to a decimal string', function () {
    expect(Money::toDecimalString(24950, 'MAD'))->toBe('249.50')
        ->and(Money::toDecimalString(5, 'MAD'))->toBe('0.05')
        ->and(Money::toDecimalString(null, 'MAD'))->toBe('');
});

it('computes percentages with half-up rounding', function () {
    expect(Money::percentOf(10000, 10))->toBe(1000)
        ->and(Money::percentOf(995, 10))->toBe(100) // 99.5 → 100
        ->and(Money::percentOf(994, 10))->toBe(99)  // 99.4 → 99
        ->and(Money::percentOf(12345, 100))->toBe(12345)
        ->and(Money::percentOf(12345, 0))->toBe(0);
});
