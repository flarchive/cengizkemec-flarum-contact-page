<?php

namespace Cengizkemec\ContactPage\Tests\integration;

use Cengizkemec\ContactPage\Tests\Support\CapturingTransport;

class AccessControlTest extends ContactPageTestCase
{
    public function test_guests_can_submit_when_everyone_mode_is_enabled(): void
    {
        $response = $this->sendContact();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_registered_users_can_submit(): void
    {
        $response = $this->sendContact([], 2);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_registered_only_mode_denies_guests(): void
    {
        $this->contactSettings(['access_mode' => 'registered']);

        $response = $this->sendContact();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('permission_denied', $this->responseErrors($response)[0]['code'] ?? null);
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_guests_only_mode_denies_registered_users(): void
    {
        $this->contactSettings(['access_mode' => 'guests']);

        $response = $this->sendContact([], 2);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('guests only', strtolower((string) ($this->responseErrors($response)[0]['detail'] ?? '')));
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_disabled_mode_denies_everyone(): void
    {
        $this->contactSettings(['access_mode' => 'disabled']);

        $guest = $this->sendContact();
        $user = $this->sendContact([], 2);

        $this->assertSame(403, $guest->getStatusCode());
        $this->assertSame(403, $user->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_guest_submissions_can_be_disabled(): void
    {
        $this->contactSettings(['allow_guest_submissions' => false]);

        $response = $this->sendContact();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_registered_submissions_can_be_disabled(): void
    {
        $this->contactSettings(['allow_registered_submissions' => false]);

        $response = $this->sendContact([], 2);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_unverified_registered_user_is_denied_when_verification_is_required(): void
    {
        $this->contactSettings(['require_email_verification' => true]);
        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                array_merge($this->normalUser(), [
                    'id' => 3,
                    'username' => 'unverified',
                    'email' => 'unverified@example.com',
                    'is_email_confirmed' => 0,
                ]),
            ],
        ]);

        $response = $this->sendContact([], 3);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('verify', strtolower((string) ($this->responseErrors($response)[0]['detail'] ?? '')));
        $this->assertCount(0, CapturingTransport::$messages);
    }
}
