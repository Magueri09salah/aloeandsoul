<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * Country-aware phone validation and E.164 normalisation.
 */
final class Phone
{
    /**
     * Normalise a phone number entered by a customer for the given region
     * (ISO 3166-1 alpha-2). Returns E.164 ("+212612345678") or null if the
     * number is not a valid, callable number for that region.
     */
    public static function normalize(?string $raw, string $region): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();
        $cleaned = preg_replace('/[^\d+]/', '', $raw) ?? '';
        // "00" international prefix → "+"
        if (str_starts_with($cleaned, '00')) {
            $cleaned = '+'.substr($cleaned, 2);
        }

        try {
            $number = $util->parse($cleaned, strtoupper($region));
        } catch (NumberParseException) {
            return null;
        }

        if (! $util->isValidNumber($number)) {
            return null;
        }

        $type = $util->getNumberType($number);
        $callable = [
            PhoneNumberType::MOBILE,
            PhoneNumberType::FIXED_LINE,
            PhoneNumberType::FIXED_LINE_OR_MOBILE,
        ];
        if (! in_array($type, $callable, true)) {
            return null;
        }

        return $util->format($number, PhoneNumberFormat::E164);
    }

    /** E.164 → national display format, e.g. "06 12 34 56 78". */
    public static function display(string $e164, string $region): string
    {
        $util = PhoneNumberUtil::getInstance();

        try {
            $number = $util->parse($e164, strtoupper($region));
        } catch (NumberParseException) {
            return $e164;
        }

        $format = $util->getRegionCodeForNumber($number) === strtoupper($region)
            ? PhoneNumberFormat::NATIONAL
            : PhoneNumberFormat::INTERNATIONAL;

        return $util->format($number, $format);
    }

    /** Digits-only international form for wa.me links. */
    public static function whatsappDigits(string $e164): string
    {
        return ltrim($e164, '+');
    }
}
