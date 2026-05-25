<?php
declare(strict_types=1);
namespace Settle;

/**
 * Email obfuscation helper.
 *
 * Renders email addresses in HTML in a form that's invisible to naive
 * scrapers but decodes transparently in a real browser via a tiny JS
 * snippet (see assets/js/admin.js).
 *
 * Approach: each character of the address is XORed against a random
 * single-byte key, the result is hex-encoded, and the key is prepended
 * to the hex string. The browser-side JS reverses this. Bots that don't
 * execute JavaScript see only a hex blob; clicks degrade gracefully
 * because we also wire the click handler to construct the mailto: at
 * the moment of activation.
 *
 * Usage in templates:
 *     <?= \Settle\EmailObfuscator::link($staff['email']) ?>
 *     <?= \Settle\EmailObfuscator::link($staff['email'], $staff['full_name']) ?>
 *
 * Returns an empty string when given an empty/invalid address, so it's
 * safe to call unconditionally on nullable fields.
 */
final class EmailObfuscator
{
    /**
     * Render an obfuscated <a> tag for an email address.
     *
     * @param string|null $address    The email address (anything falsy → '').
     * @param string|null $linkText   Visible link text. Defaults to the
     *                                obfuscated address (decoded by JS).
     */
    public static function link(?string $address, ?string $linkText = null): string
    {
        $address = trim((string)$address);
        if ($address === '' || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        $encoded = self::encode($address);

        // If link text wasn't given, use a span the JS will overwrite with
        // the decoded address. We deliberately don't put the plaintext
        // address in any attribute or text node.
        $textHtml = $linkText !== null && $linkText !== ''
            ? htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8')
            : '<span class="protected-email-text" data-email="' .
              htmlspecialchars($encoded, ENT_QUOTES, 'UTF-8') . '">' .
              '[email&nbsp;protected]</span>';

        // The link itself has no real href until JS rewrites it. data-email
        // carries the encoded payload; href is a no-op for non-JS scrapers.
        return '<a href="#" class="protected-email" '
             . 'data-email="' . htmlspecialchars($encoded, ENT_QUOTES, 'UTF-8') . '" '
             . 'rel="nofollow">'
             . $textHtml
             . '</a>';
    }

    /**
     * Render only an obfuscated text representation (no <a> tag).
     * Useful in places where a clickable link doesn't make sense,
     * such as inside a sentence: "Reach Mark at <span>...</span>".
     */
    public static function text(?string $address): string
    {
        $address = trim((string)$address);
        if ($address === '' || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        $encoded = self::encode($address);
        return '<span class="protected-email-text" data-email="'
             . htmlspecialchars($encoded, ENT_QUOTES, 'UTF-8') . '">'
             . '[email&nbsp;protected]</span>';
    }

    /**
     * XOR-encode an address against a random single-byte key, then hex.
     * Output format: first two hex chars are the key, the rest are the
     * XORed-and-hexed address. Matches the decoder in admin.js.
     */
    private static function encode(string $address): string
    {
        // random_int is cryptographic; not strictly necessary for an
        // obfuscator but it's already available and costs nothing.
        $key = random_int(1, 255);
        $out = sprintf('%02x', $key);
        for ($i = 0, $n = strlen($address); $i < $n; $i++) {
            $out .= sprintf('%02x', ord($address[$i]) ^ $key);
        }
        return $out;
    }
}
