<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Support;

/**
 * Removes the most common sensitive values from a log line.
 *
 * We never pass visitor names, messages or email addresses to the logger, but
 * an SMTP exception can still echo credentials or an address. Redacting here
 * gives a second line of defence.
 */
final class LogSanitizer
{
    public static function redact(string $message): string
    {
        $message = preg_replace(
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
            '[email]',
            $message
        ) ?? $message;

        $message = preg_replace(
            '/\b(password|passwd|secret|token|api[_-]?key)\b\s*[:=]\s*\S+/i',
            '$1=[redacted]',
            $message
        ) ?? $message;

        $message = preg_replace('/\bBearer\s+\S+/i', 'Bearer [redacted]', $message) ?? $message;

        return mb_substr($message, 0, 500, 'UTF-8');
    }
}
