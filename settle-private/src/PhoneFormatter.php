<?php
declare(strict_types=1);
namespace Settle;

/**
 * Display-only phone number formatter.
 *
 * Normalizes US phone numbers to the conventional (###) ###-#### format
 * regardless of how they were entered. Anything that doesn't look like
 * a US number is returned unchanged so we don't mangle international
 * numbers or numbers with extensions.
 *
 * The database stores the raw input as the staff member typed it; this
 * helper only affects rendering. That way the admin form always shows
 * what the user originally entered (so they can correct typos), but
 * the public-facing page is consistent.
 *
 * Usage:
 *   <?= htmlspecialchars(\Settle\PhoneFormatter::formatUs($phone), ENT_QUOTES) ?>
 *   <?= \Settle\PhoneFormatter::telHref($phone) ?>  // for tel: links
 */
final class PhoneFormatter
{
    /**
     * Format a phone number for display.
     * Returns the original input untouched if it doesn't look US.
     */
    public static function formatUs(?string $raw): string
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        // 11 digits with a leading 1 (US country code) — strip the 1.
        if (strlen($digits) === 11 && $digits[0] === '1') {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 4)
            );
        }

        // Not a recognizable US number — give back what we got.
        return $raw;
    }

    /**
     * Build a tel: href value: digits only, with a leading + when the
     * number includes a country code.
     */
    public static function telHref(?string $raw): string
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return '';
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return '';
        }
        // If the user typed a +, preserve the international intent.
        $hadPlus = strpos($raw, '+') !== false;
        return ($hadPlus ? '+' : '') . $digits;
    }
}
