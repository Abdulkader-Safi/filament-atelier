<?php

declare(strict_types=1);

namespace Safi\Atelier;

/**
 * A URL typed in the panel, made safe to put in an href.
 *
 * Blade escapes the value, which stops it breaking out of the attribute and
 * does nothing at all about `javascript:alert(1)`. That is a stored XSS
 * against every visitor, authored by someone with panel access, and a client
 * pasting a tracking link they were sent is a plausible way in.
 *
 * An allowlist rather than a blocklist: `data:`, `vbscript:` and whatever the
 * next one turns out to be are all refused by not being on it.
 */
class Url
{
    /** Schemes a link may carry. Everything else is refused. */
    public const SCHEMES = ['http', 'https', 'mailto', 'tel', 'sms'];

    /**
     * The URL, or the fallback when it names a scheme we do not allow.
     *
     * Relative URLs, anchors and query strings pass through untouched, since
     * they carry no scheme and are the common case.
     */
    public static function safe(mixed $url, string $fallback = '#'): string
    {
        if (! is_string($url) || trim($url) === '') {
            return $fallback;
        }

        $url = trim($url);

        // A scheme is everything before the first colon, and only when that
        // colon comes before any slash, question mark or hash. `/a:b`,
        // `?x=1:2` and `#a:b` are paths, not schemes.
        if (! preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $matches)) {
            return $url;
        }

        $upTo = strpos($url, ':');

        foreach (['/', '?', '#'] as $delimiter) {
            $position = strpos($url, $delimiter);

            if ($position !== false && $position < $upTo) {
                return $url;
            }
        }

        return in_array(strtolower($matches[1]), self::SCHEMES, true) ? $url : $fallback;
    }
}
