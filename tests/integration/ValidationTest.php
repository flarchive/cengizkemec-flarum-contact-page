<?php

namespace Cengizkemec\ContactPage\Tests\integration;

use Cengizkemec\ContactPage\Tests\Support\CapturingTransport;

class ValidationTest extends ContactPageTestCase
{
    public function test_invalid_email_is_rejected(): void
    {
        $response = $this->sendContact(['email' => 'not-an-email']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('valid email', strtolower($this->detail($response)));
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_empty_required_fields_are_rejected(): void
    {
        $response = $this->sendContact([
            'name' => '',
            'email' => '',
            'subject' => '',
            'message' => '',
            'privacy' => '0',
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertCount(5, $this->responseErrors($response));
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_message_minimum_length_is_enforced(): void
    {
        $this->contactSettings(['message_min_length' => 20]);

        $response = $this->sendContact(['message' => 'Too short']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('at least 20', strtolower($this->detail($response)));
    }

    public function test_message_maximum_length_is_enforced(): void
    {
        $this->contactSettings(['message_max_length' => 20]);

        $response = $this->sendContact(['message' => str_repeat('a', 21)]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('not be longer than 20', strtolower($this->detail($response)));
    }

    public function test_subject_maximum_length_is_enforced(): void
    {
        $this->contactSettings(['subject_max_length' => 10]);

        $response = $this->sendContact(['subject' => str_repeat('s', 11)]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('not be longer than 10', strtolower($this->detail($response)));
    }

    public function test_privacy_acceptance_is_required_when_enabled(): void
    {
        $response = $this->sendContact(['privacy' => '0']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('privacy', strtolower($this->detail($response)));
    }

    public function test_phone_validation_only_applies_when_enabled(): void
    {
        $this->contactSettings([
            'field_phone_enabled' => true,
            'field_phone_required' => false,
        ]);

        $response = $this->sendContact(['phone' => 'call-me-maybe']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('phone', strtolower($this->detail($response)));
    }

    public function test_category_must_be_one_of_the_configured_values(): void
    {
        $this->contactSettings([
            'field_category_enabled' => true,
            'field_category_required' => true,
            'categories' => "General\nSupport",
        ]);

        $response = $this->sendContact(['category' => 'Unknown']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('category', strtolower($this->detail($response)));
    }

    public function test_disabled_fields_are_ignored(): void
    {
        $this->contactSettings(['field_name_enabled' => false]);

        $response = $this->sendContact(['name' => '']);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertCount(1, CapturingTransport::$messages);
    }

    private function detail($response): string
    {
        $errors = $this->responseErrors($response);

        return (string) ($errors[0]['detail'] ?? '');
    }
}
