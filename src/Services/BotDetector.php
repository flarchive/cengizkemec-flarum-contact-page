<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Services;

use Cengizkemec\ContactPage\Support\ContactSettings;

/**
 * Cheap, privacy-friendly bot signals.
 *
 * A honeypot is only a deterrent; it is deliberately combined with server-side
 * validation, Turnstile (optional) and rate limiting. Detected bots receive the
 * normal success response but no email is sent, so the trap is not disclosed.
 */
final class BotDetector
{
    public const HONEYPOT_FIELD = 'contact_hp';
    public const ELAPSED_FIELD = 'elapsedMs';

    private const MAX_FORM_AGE_MS = 6 * 60 * 60 * 1000;

    public function __construct(
        private readonly ContactSettings $settings
    ) {
    }

    public function reason(array $input): ?string
    {
        if ($this->settings->bool('honeypot_enabled')) {
            $honeypot = $input[self::HONEYPOT_FIELD] ?? '';

            if (is_scalar($honeypot) && trim((string) $honeypot) !== '') {
                return 'honeypot';
            }
        }

        if ($this->settings->bool('time_trap_enabled')) {
            $elapsed = $input[self::ELAPSED_FIELD] ?? null;

            if (! is_numeric($elapsed)) {
                return 'time_trap_missing';
            }

            $elapsed = (int) $elapsed;
            $minimum = $this->settings->timeTrapSeconds() * 1000;

            if ($elapsed < $minimum || $elapsed > self::MAX_FORM_AGE_MS) {
                return 'time_trap';
            }
        }

        return null;
    }
}
