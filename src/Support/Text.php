<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Support;

/**
 * Small, dependency-free text normalisation helpers.
 *
 * Input is never trusted. Normalising before validation and before rendering
 * gives every later layer (logs, mail headers, Blade views) predictable data.
 */
final class Text
{
    /**
     * Normalise a value used on a single line (name, subject, phone, ...).
     */
    public static function singleLine(mixed $value, bool $stripTags = true): string
    {
        $value = self::base($value);

        if ($stripTags) {
            $value = strip_tags($value);
        }

        // Collapse all whitespace (including newlines and tabs) to one space.
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    /**
     * Normalise a multiline value (the message body).
     */
    public static function multiline(mixed $value): string
    {
        $value = self::base($value);

        // Normalise line endings and remove excessive blank runs without
        // destroying intentional paragraph breaks.
        $value = preg_replace("/\n{4,}/", "\n\n\n", $value) ?? $value;

        return trim($value);
    }

    public static function length(string $value): int
    {
        return mb_strlen($value, 'UTF-8');
    }

    public static function containsNewline(string $value): bool
    {
        return str_contains($value, "\n") || str_contains($value, "\r");
    }

    private static function base(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = (string) $value;

        // Drop NUL bytes and every control character except tab/newline.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
            if (is_string($normalized)) {
                $value = $normalized;
            }
        }

        return str_replace(["\r\n", "\r"], "\n", $value);
    }
}
