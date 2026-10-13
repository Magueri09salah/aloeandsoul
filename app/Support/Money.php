<?php

namespace App\Support;

use InvalidArgumentException;
use NumberFormatter;

/**
 * Integer minor-unit money helpers. Amounts are never floats.
 */
final class Money
{
    /** Minor-unit exponent per ISO 4217 currency; 2 unless listed. */
    private const EXPONENTS = [
        'JPY' => 0, 'KRW' => 0, 'XOF' => 0, 'XAF' => 0,
        'BHD' => 3, 'KWD' => 3, 'OMR' => 3, 'TND' => 3, 'JOD' => 3,
    ];

    public static function exponent(string $currency): int
    {
        return self::EXPONENTS[strtoupper($currency)] ?? 2;
    }

    /**
     * Parse a user-entered decimal string ("149.5", "149,50", "1 200") into
     * minor units without floating-point arithmetic.
     */
    public static function parse(string|int|null $input, string $currency): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }

        $exponent = self::exponent($currency);
        $normalised = str_replace([' ', "\u{00A0}", "\u{202F}"], '', (string) $input);
        $normalised = str_replace(',', '.', $normalised);

        if (! preg_match('/^(\d{1,9})(?:\.(\d+))?$/', $normalised, $m)) {
            throw new InvalidArgumentException("Invalid amount [{$input}].");
        }

        $fraction = $m[2] ?? '';
        if (strlen($fraction) > $exponent) {
            throw new InvalidArgumentException("Too many decimal places in [{$input}].");
        }

        return (int) $m[1] * (10 ** $exponent) + (int) str_pad($fraction, $exponent, '0');
    }

    /** Minor units → plain decimal string for form inputs ("149.50"). */
    public static function toDecimalString(?int $minor, string $currency): string
    {
        if ($minor === null) {
            return '';
        }

        $exponent = self::exponent($currency);
        if ($exponent === 0) {
            return (string) $minor;
        }

        $divisor = 10 ** $exponent;

        return intdiv($minor, $divisor).'.'.str_pad((string) ($minor % $divisor), $exponent, '0', STR_PAD_LEFT);
    }

    /** Percentage of an amount, rounded half-up to the nearest minor unit. */
    public static function percentOf(int $minor, int $percent): int
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Percent must be between 0 and 100.');
        }

        return intdiv($minor * $percent + 50, 100);
    }

    /** Human-readable amount for server-rendered output (packing slips, emails). */
    public static function format(int $minor, string $currency, string $locale = 'en'): string
    {
        $exponent = self::exponent($currency);

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $exponent);
            $formatted = $formatter->formatCurrency($minor / (10 ** $exponent), $currency);
            if ($formatted !== false) {
                return $formatted;
            }
        }

        return self::toDecimalString($minor, $currency).' '.$currency;
    }
}
