<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Support;

use Exception;

/**
 * A controlled, user-facing failure.
 *
 * The controller turns this into a JSON:API error response. The message key is
 * translated at response time so the visitor sees the current forum language.
 * logContext must never contain the visitor's message, email address, raw IP or
 * a Turnstile token.
 */
final class ContactException extends Exception
{
    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $logContext
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        public readonly string $messageKey,
        public readonly array $params = [],
        public readonly array $logContext = []
    ) {
        parent::__construct($errorCode);
    }
}
