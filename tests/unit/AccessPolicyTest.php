<?php

namespace Cengizkemec\ContactPage\Tests\unit;

use Cengizkemec\ContactPage\Services\AccessPolicy;
use Cengizkemec\ContactPage\Support\ContactSettings;
use Cengizkemec\ContactPage\Tests\Support\SettingsMock;
use Flarum\User\User;
use PHPUnit\Framework\TestCase;

class AccessPolicyTest extends TestCase
{
    public function test_everyone_mode_allows_guests_and_members(): void
    {
        $policy = $this->policy(['access_mode' => 'everyone']);

        $this->assertNull($policy->denialKey($this->guest()));
        $this->assertNull($policy->denialKey($this->member(true)));
    }

    public function test_registered_mode_denies_guests(): void
    {
        $policy = $this->policy(['access_mode' => 'registered']);

        $this->assertSame(
            'cengizkemec-contact-page.forum.access.guests_not_allowed',
            $policy->denialKey($this->guest())
        );
        $this->assertNull($policy->denialKey($this->member(true)));
    }

    public function test_guests_mode_denies_members(): void
    {
        $policy = $this->policy(['access_mode' => 'guests']);

        $this->assertSame(
            'cengizkemec-contact-page.forum.access.registered_not_allowed',
            $policy->denialKey($this->member(true))
        );
        $this->assertNull($policy->denialKey($this->guest()));
    }

    public function test_disabled_page_and_mode_fail_closed(): void
    {
        $disabledPage = $this->policy(['enabled' => false]);
        $disabledMode = $this->policy(['access_mode' => 'disabled']);
        $unknownMode = $this->policy(['access_mode' => 'someone-else']);

        $expected = 'cengizkemec-contact-page.forum.access.disabled';

        $this->assertSame($expected, $disabledPage->denialKey($this->guest()));
        $this->assertSame($expected, $disabledMode->denialKey($this->member(true)));
        $this->assertSame($expected, $unknownMode->denialKey($this->member(true)));
    }

    public function test_allow_switches_narrow_access(): void
    {
        $noGuests = $this->policy(['allow_guest_submissions' => false]);
        $noMembers = $this->policy(['allow_registered_submissions' => false]);

        $this->assertSame(
            'cengizkemec-contact-page.forum.access.guest_submissions_disabled',
            $noGuests->denialKey($this->guest())
        );
        $this->assertSame(
            'cengizkemec-contact-page.forum.access.registered_submissions_disabled',
            $noMembers->denialKey($this->member(true))
        );
    }

    public function test_email_verification_only_affects_members(): void
    {
        $policy = $this->policy(['require_email_verification' => true]);

        $this->assertSame(
            'cengizkemec-contact-page.forum.access.email_verification_required',
            $policy->denialKey($this->member(false))
        );
        $this->assertNull($policy->denialKey($this->member(true)));
        $this->assertNull($policy->denialKey($this->guest()));
    }

    private function policy(array $overrides): AccessPolicy
    {
        return new AccessPolicy(new ContactSettings(SettingsMock::make(array_merge(['enabled' => true], $overrides))));
    }

    private function guest(): User
    {
        return new User();
    }

    private function member(bool $verified): User
    {
        $user = new User();
        $user->exists = true;
        $user->id = 2;
        $user->is_email_confirmed = $verified;

        return $user;
    }
}
