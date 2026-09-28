<?php

namespace App\Support;

/**
 * Best-effort E.164 normalisation, tuned for Indian numbering but generic
 * enough not to mangle international input.
 *
 * Deliberately conservative: when a value cannot be confidently normalised
 * this returns null so the caller can reject the row rather than dialling
 * something wrong.
 */
final class PhoneNumber
{
    /**
     * @return string|null E.164 (e.g. +919876543210) or null when invalid.
     */
    public static function normalize(?string $raw, ?string $defaultCountryCode = null): ?string
    {
        if ($raw === null) {
            return null;
        }

        $cc = $defaultCountryCode ?: (string) config('sarvam.default_country_code', '91');

        // Spreadsheets love turning phone numbers into floats: 9.1988e+11
        $raw = trim($raw);
        if ($raw !== '' && preg_match('/^\d+(\.\d+)?[eE][+-]?\d+$/', $raw)) {
            $raw = number_format((float) $raw, 0, '.', '');
        }

        $hadPlus = str_starts_with($raw, '+');
        $digits  = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        // 00 international prefix behaves like a leading +
        if (! $hadPlus && str_starts_with($digits, '00')) {
            $digits  = substr($digits, 2);
            $hadPlus = true;
        }

        if (! $hadPlus) {
            // Indian trunk prefix: 09876543210 -> 9876543210
            if ($cc === '91' && strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }

            // Bare national number -> prepend the configured country code.
            if ($cc === '91' && strlen($digits) === 10) {
                $digits = $cc . $digits;
            } elseif (! str_starts_with($digits, $cc) && strlen($digits) < 11) {
                $digits = $cc . $digits;
            }
        }

        // E.164 allows at most 15 digits, and a country code is at least 1.
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        // Indian mobile numbers start 6-9 after the country code.
        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            if (! preg_match('/^91[6-9]\d{9}$/', $digits)) {
                return null;
            }
        }

        return '+' . $digits;
    }

    /** Human-friendly rendering for the UI. Never used for dialling. */
    public static function display(?string $e164): string
    {
        if (! $e164) {
            return '--';
        }

        if (preg_match('/^\+91(\d{5})(\d{5})$/', $e164, $m)) {
            return "+91 {$m[1]} {$m[2]}";
        }

        return $e164;
    }

    public static function isValid(?string $raw): bool
    {
        return self::normalize($raw) !== null;
    }
}
