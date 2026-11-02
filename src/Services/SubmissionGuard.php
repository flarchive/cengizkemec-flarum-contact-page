<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Services;

use Cengizkemec\ContactPage\Support\ContactException;
use Cengizkemec\ContactPage\Support\ContactSettings;
use Cengizkemec\ContactPage\Support\PrivacyHasher;
use Flarum\User\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository;

/**
 * Rate limiting, cooldown and duplicate-submission protection.
 *
 * All cache keys contain only HMACs. No raw IP address or message content is
 * written to the cache, and every entry has a short, configurable TTL.
 */
final class SubmissionGuard
{
    private const BYPASS_PERMISSION = 'cengizkemec-contact-page.bypassRateLimit';

    private readonly RateLimiter $limiter;

    public function __construct(
        private readonly Repository $cache,
        private readonly ContactSettings $settings,
        private readonly PrivacyHasher $hasher
    ) {
        $this->limiter = new RateLimiter($cache);
    }

    public function assertRateLimit(User $actor, string $ipHash): void
    {
        if (! $this->settings->rateLimitEnabled() || $actor->can(self::BYPASS_PERMISSION)) {
            return;
        }

        $cooldown = $this->settings->cooldownSeconds();

        if ($cooldown > 0) {
            $cooldownKey = $this->cooldownKey($ipHash);

            if ($this->limiter->tooManyAttempts($cooldownKey, 1)) {
                throw new ContactException(
                    429,
                    'too_many_requests',
                    'cengizkemec-contact-page.forum.errors.rate_limited',
                    ['seconds' => max(1, $this->limiter->availableIn($cooldownKey))],
                    ['reason' => 'cooldown']
                );
            }
        }

        $windowKey = $this->windowKey($ipHash);

        if ($this->limiter->tooManyAttempts($windowKey, $this->settings->rateLimitMax())) {
            throw new ContactException(
                429,
                'too_many_requests',
                'cengizkemec-contact-page.forum.errors.rate_limited',
                ['seconds' => max(1, $this->limiter->availableIn($windowKey))],
                ['reason' => 'window']
            );
        }
    }

    public function assertNotDuplicate(string $ipHash, array $data): void
    {
        $window = $this->settings->duplicateWindowSeconds();

        if ($window === 0) {
            return;
        }

        $key = $this->duplicateKey($ipHash, $data);

        // add() is atomic on the cache stores Flarum supports, so two
        // simultaneous submissions cannot both pass this check.
        if (! $this->cache->add($key, 1, $window)) {
            throw new ContactException(
                409,
                'duplicate_submission',
                'cengizkemec-contact-page.forum.errors.duplicate',
                [],
                ['reason' => 'duplicate']
            );
        }
    }

    public function record(string $ipHash): void
    {
        if (! $this->settings->rateLimitEnabled()) {
            return;
        }

        $cooldown = $this->settings->cooldownSeconds();

        if ($cooldown > 0) {
            $this->limiter->hit($this->cooldownKey($ipHash), $cooldown);
        }

        $this->limiter->hit($this->windowKey($ipHash), $this->settings->rateLimitWindow());
    }

    /**
     * Undo the guard after the mail transport failed, so the visitor can retry
     * immediately once the administrator has fixed the mail configuration.
     */
    public function release(string $ipHash, array $data): void
    {
        $this->limiter->clear($this->cooldownKey($ipHash));
        $this->limiter->clear($this->windowKey($ipHash));

        if ($this->settings->duplicateWindowSeconds() > 0) {
            $this->cache->forget($this->duplicateKey($ipHash, $data));
        }
    }

    private function cooldownKey(string $ipHash): string
    {
        return 'cengizkemec-contact-page:cooldown:'.$ipHash;
    }

    private function windowKey(string $ipHash): string
    {
        return 'cengizkemec-contact-page:window:'.$ipHash;
    }

    private function duplicateKey(string $ipHash, array $data): string
    {
        $fingerprint = implode("\n", [
            $ipHash,
            (string) ($data['email'] ?? ''),
            (string) ($data['subject'] ?? ''),
            (string) ($data['message'] ?? ''),
            (string) ($data['category'] ?? ''),
        ]);

        return 'cengizkemec-contact-page:duplicate:'.$this->hasher->hash($fingerprint);
    }
}
