<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Services;

use Cengizkemec\ContactPage\Support\ContactSettings;
use Flarum\User\User;

/**
 * Server-side source of truth for contact form access.
 *
 * The JavaScript hides navigation items for convenience, but every submission
 * passes through this policy. The mode is applied first, then the optional
 * allow/deny switches narrow access further.
 */
final class AccessPolicy
{
    public const MODE_EVERYONE = 'everyone';
    public const MODE_REGISTERED = 'registered';
    public const MODE_GUESTS = 'guests';
    public const MODE_DISABLED = 'disabled';

    public function __construct(
        private readonly ContactSettings $settings
    ) {
    }

    public function denialKey(User $actor): ?string
    {
        if (! $this->settings->bool('enabled')) {
            return 'cengizkemec-contact-page.forum.access.disabled';
        }

        $mode = $this->settings->string('access_mode');

        if ($mode === self::MODE_DISABLED || ! in_array($mode, $this->modes(), true)) {
            return 'cengizkemec-contact-page.forum.access.disabled';
        }

        if (! $actor->exists) {
            if (! in_array($mode, [self::MODE_EVERYONE, self::MODE_GUESTS], true)) {
                return 'cengizkemec-contact-page.forum.access.guests_not_allowed';
            }

            if (! $this->settings->bool('allow_guest_submissions')) {
                return 'cengizkemec-contact-page.forum.access.guest_submissions_disabled';
            }

            return null;
        }

        if ($mode === self::MODE_GUESTS) {
            return 'cengizkemec-contact-page.forum.access.registered_not_allowed';
        }

        if (! $this->settings->bool('allow_registered_submissions')) {
            return 'cengizkemec-contact-page.forum.access.registered_submissions_disabled';
        }

        if ($this->settings->bool('require_email_verification') && ! $actor->is_email_confirmed) {
            return 'cengizkemec-contact-page.forum.access.email_verification_required';
        }

        return null;
    }

    public function allows(User $actor): bool
    {
        return $this->denialKey($actor) === null;
    }

    /**
     * @return string[]
     */
    private function modes(): array
    {
        return [
            self::MODE_EVERYONE,
            self::MODE_REGISTERED,
            self::MODE_GUESTS,
            self::MODE_DISABLED,
        ];
    }
}
