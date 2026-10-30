<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Support;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * HMAC-based pseudonymisation for values that must not be stored raw.
 *
 * The salt is generated once per installation and never leaves the settings
 * table. Hashes are only used as cache keys with a short TTL and are not
 * linked to any persistent user record.
 */
final class PrivacyHasher
{
    public function __construct(
        private readonly SettingsRepositoryInterface $settings
    ) {
    }

    public function hash(string $value): string
    {
        return hash_hmac('sha256', $value, $this->salt());
    }

    private function salt(): string
    {
        $key = Defaults::key('security_salt');
        $salt = $this->settings->get($key);

        if (! is_string($salt) || strlen($salt) < 64) {
            $salt = bin2hex(random_bytes(32));
            $this->settings->set($key, $salt);
        }

        return $salt;
    }
}
