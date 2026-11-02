<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Services\Turnstile;

use Cengizkemec\ContactPage\Support\ContactException;
use Cengizkemec\ContactPage\Support\ContactSettings;
use Cengizkemec\ContactPage\Support\PrivacyHasher;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Server-side Turnstile verification.
 *
 * Cloudflare's result is checked for hostname, action and age, and successful
 * tokens are additionally remembered for a few minutes to reject replays even
 * if the Siteverify response is tampered with or cached upstream.
 */
final class TurnstileVerifier
{
    public const EXPECTED_ACTION = 'contact';

    private const MAX_TOKEN_LENGTH = 4096;
    private const MAX_CHALLENGE_AGE_SECONDS = 300;
    private const REPLAY_TTL_SECONDS = 600;

    public function __construct(
        private readonly ContactSettings $settings,
        private readonly TurnstileClientInterface $client,
        private readonly Repository $cache,
        private readonly PrivacyHasher $hasher,
        private readonly LoggerInterface $logger
    ) {
    }

    public function verify(string $token, string $requestHost): void
    {
        if (! $this->settings->turnstileEnabled()) {
            return;
        }

        $secret = $this->settings->turnstileSecretKey();
        $siteKey = $this->settings->turnstileSiteKey();

        if ($secret === '' || $siteKey === '') {
            $this->logger->error('Contact Page Turnstile is enabled but its keys are missing.');

            throw new ContactException(
                503,
                'turnstile_unavailable',
                'cengizkemec-contact-page.forum.errors.turnstile_unavailable',
                [],
                ['reason' => 'missing_keys']
            );
        }

        $token = trim($token);

        if ($token === '' || strlen($token) > self::MAX_TOKEN_LENGTH) {
            throw new ContactException(
                422,
                'turnstile_missing',
                'cengizkemec-contact-page.forum.errors.turnstile_missing',
                [],
                ['reason' => 'missing_token']
            );
        }

        $result = $this->client->verify($token, $secret, $this->settings->turnstileTimeout());

        if ($result->transportError) {
            $this->logger->warning('Contact Page Turnstile verification could not be completed.', [
                'reason' => 'transport_error',
                'context' => $result->logContext,
            ]);

            throw new ContactException(
                503,
                'turnstile_unavailable',
                'cengizkemec-contact-page.forum.errors.turnstile_unavailable',
                [],
                ['reason' => 'transport_error']
            );
        }

        if (! $result->success) {
            $duplicate = in_array('timeout-or-duplicate', $result->errorCodes, true);

            $this->logger->warning('Contact Page Turnstile rejected a token.', [
                'reason' => $duplicate ? 'timeout_or_duplicate' : 'verification_failed',
                'error_codes' => $result->errorCodes,
            ]);

            throw new ContactException(
                422,
                $duplicate ? 'turnstile_duplicate' : 'turnstile_failed',
                $duplicate
                    ? 'cengizkemec-contact-page.forum.errors.turnstile_duplicate'
                    : 'cengizkemec-contact-page.forum.errors.turnstile_failed',
                [],
                ['reason' => $duplicate ? 'timeout_or_duplicate' : 'verification_failed']
            );
        }

        $this->assertHostname($result->hostname, $requestHost);
        $this->assertAction($result->action);
        $this->assertFresh($result->challengeTs);
        $this->assertNotReplayed($token);
    }

    private function assertHostname(?string $actual, string $requestHost): void
    {
        $configured = $this->settings->string('turnstile_expected_hostname');
        $expected = $this->normalizeHost($configured !== '' ? $configured : $requestHost);
        $actual = $actual !== null ? $this->normalizeHost($actual) : '';

        if ($expected === '' || $actual === '' || ! hash_equals($expected, $actual)) {
            $this->logger->warning('Contact Page Turnstile hostname did not match.', [
                'reason' => 'hostname_mismatch',
            ]);

            throw new ContactException(
                422,
                'turnstile_hostname',
                'cengizkemec-contact-page.forum.errors.turnstile_failed',
                [],
                ['reason' => 'hostname_mismatch']
            );
        }
    }

    private function assertAction(?string $action): void
    {
        if ($action !== self::EXPECTED_ACTION) {
            $this->logger->warning('Contact Page Turnstile action did not match.', [
                'reason' => 'action_mismatch',
            ]);

            throw new ContactException(
                422,
                'turnstile_action',
                'cengizkemec-contact-page.forum.errors.turnstile_failed',
                [],
                ['reason' => 'action_mismatch']
            );
        }
    }

    private function assertFresh(?string $challengeTs): void
    {
        if ($challengeTs === null) {
            $this->throwExpired();
        }

        try {
            $challenge = new DateTimeImmutable($challengeTs);
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $age = $now->getTimestamp() - $challenge->getTimestamp();
        } catch (Throwable) {
            $this->throwExpired();
        }

        if ($age > self::MAX_CHALLENGE_AGE_SECONDS || $age < -60) {
            $this->throwExpired();
        }
    }

    private function assertNotReplayed(string $token): void
    {
        $key = 'cengizkemec-contact-page:turnstile:'.$this->hasher->hash($token);

        try {
            $fresh = $this->cache->add($key, 1, self::REPLAY_TTL_SECONDS);
        } catch (Throwable $e) {
            $this->logger->error('Contact Page Turnstile replay store failed.', [
                'exception_class' => $e::class,
            ]);

            throw new ContactException(
                503,
                'turnstile_unavailable',
                'cengizkemec-contact-page.forum.errors.turnstile_unavailable',
                [],
                ['reason' => 'cache_error']
            );
        }

        if (! $fresh) {
            $this->logger->warning('Contact Page Turnstile token was replayed.', [
                'reason' => 'duplicate_token',
            ]);

            throw new ContactException(
                422,
                'turnstile_duplicate',
                'cengizkemec-contact-page.forum.errors.turnstile_duplicate',
                [],
                ['reason' => 'duplicate_token']
            );
        }
    }

    private function throwExpired(): never
    {
        $this->logger->warning('Contact Page Turnstile token was expired or malformed.', [
            'reason' => 'expired',
        ]);

        throw new ContactException(
            422,
            'turnstile_expired',
            'cengizkemec-contact-page.forum.errors.turnstile_expired',
            [],
            ['reason' => 'expired']
        );
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        // Accept either a bare host or a value copied from a URL.
        if (str_contains($host, '://')) {
            $host = (string) (parse_url($host, PHP_URL_HOST) ?: $host);
        }

        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        return rtrim($host, '.');
    }
}
