<?php

namespace Cengizkemec\ContactPage\Tests\integration;

use Cengizkemec\ContactPage\Tests\Support\CapturingTransport;

class AbuseProtectionTest extends ContactPageTestCase
{
    public function test_honeypot_submissions_receive_a_fake_success_without_email(): void
    {
        $this->contactSettings(['honeypot_enabled' => true]);

        $response = $this->sendContact(['contact_hp' => 'https://spam.example']);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_time_trap_rejects_submissions_that_are_too_fast(): void
    {
        $this->contactSettings([
            'time_trap_enabled' => true,
            'time_trap_seconds' => 2,
        ]);

        $fast = $this->sendContact(['elapsedMs' => 100]);
        $slow = $this->sendContact([
            'elapsedMs' => 5000,
            'message' => 'A different, slower message that should be accepted.',
        ]);

        $this->assertSame(201, $fast->getStatusCode());
        $this->assertSame(201, $slow->getStatusCode());
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_per_ip_rate_limit_is_enforced(): void
    {
        $this->contactSettings([
            'rate_limit_enabled' => true,
            'rate_limit_max' => 1,
            'rate_limit_window' => 60,
            'cooldown_seconds' => 0,
        ]);

        $first = $this->sendContact();
        $second = $this->sendContact(['message' => 'A second message from the same IP address.']);

        $this->assertSame(201, $first->getStatusCode());
        $this->assertSame(429, $second->getStatusCode());
        $this->assertNotSame('', $second->getHeaderLine('Retry-After'));
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_cooldown_is_enforced(): void
    {
        $this->contactSettings([
            'rate_limit_enabled' => true,
            'rate_limit_max' => 10,
            'rate_limit_window' => 600,
            'cooldown_seconds' => 60,
        ]);

        $first = $this->sendContact();
        $second = $this->sendContact(['message' => 'A second message during the cooldown period.']);

        $this->assertSame(201, $first->getStatusCode());
        $this->assertSame(429, $second->getStatusCode());
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_duplicate_submissions_are_rejected(): void
    {
        $this->contactSettings(['duplicate_window_seconds' => 300]);

        $first = $this->sendContact();
        $second = $this->sendContact();

        $this->assertSame(201, $first->getStatusCode());
        $this->assertSame(409, $second->getStatusCode());
        $this->assertSame('duplicate_submission', $this->responseErrors($second)[0]['code'] ?? null);
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_oversized_requests_are_rejected(): void
    {
        $this->contactSettings(['max_request_bytes' => 1024]);

        $response = $this->sendContact(['message' => str_repeat('x', 2000)]);

        $this->assertSame(413, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_permission_can_bypass_the_rate_limit(): void
    {
        $this->contactSettings([
            'rate_limit_enabled' => true,
            'rate_limit_max' => 1,
            'rate_limit_window' => 60,
            'cooldown_seconds' => 0,
        ]);

        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => 3, 'permission' => 'cengizkemec-contact-page.bypassRateLimit'],
            ],
        ]);

        $first = $this->sendContact([], 2);
        $second = $this->sendContact(['message' => 'Another message from a trusted member.'], 2);

        $this->assertSame(201, $first->getStatusCode());
        $this->assertSame(201, $second->getStatusCode());
        $this->assertCount(2, CapturingTransport::$messages);
    }
}
