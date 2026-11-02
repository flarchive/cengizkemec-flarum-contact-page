<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Services\Turnstile;

/**
 * Immutable result of a Cloudflare Siteverify call.
 */
final class TurnstileResult
{
    /**
     * @param string[] $errorCodes
     * @param array<string, mixed> $logContext
     */
    private function __construct(
        public readonly bool $success,
        public readonly array $errorCodes = [],
        public readonly ?string $hostname = null,
        public readonly ?string $action = null,
        public readonly ?string $challengeTs = null,
        public readonly bool $transportError = false,
        public readonly array $logContext = []
    ) {
    }

    public static function success(?string $hostname, ?string $action, ?string $challengeTs): self
    {
        return new self(true, [], $hostname, $action, $challengeTs);
    }

    /**
     * @param string[] $errorCodes
     */
    public static function failure(array $errorCodes, ?string $hostname = null, ?string $action = null, ?string $challengeTs = null): self
    {
        return new self(false, $errorCodes, $hostname, $action, $challengeTs);
    }

    /**
     * @param array<string, mixed> $logContext
     */
    public static function transportError(array $logContext = []): self
    {
        return new self(false, ['transport-error'], null, null, null, true, $logContext);
    }
}
