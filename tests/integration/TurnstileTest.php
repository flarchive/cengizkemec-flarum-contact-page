<?php

namespace Cengizkemec\ContactPage\Tests\integration;

use Cengizkemec\ContactPage\Services\Turnstile\TurnstileResult;
use Cengizkemec\ContactPage\Tests\Support\CapturingTransport;
use Cengizkemec\ContactPage\Tests\Support\FakeTurnstileClient;

class TurnstileTest extends ContactPageTestCase
{
    private function enableTurnstile(): void
    {
        $this->contactSettings([
            'turnstile_enabled' => true,
            'turnstile_site_key' => 'test-site-key',
            'turnstile_secret_key' => 'test-secret-key',
            'turnstile_expected_hostname' => 'localhost',
        ]);

        $this->bindFakeTurnstile();
    }

    public function test_successful_verification_allows_the_email(): void
    {
        $this->enableTurnstile();

        $response = $this->sendContact(['turnstileToken' => 'valid-token']);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, FakeTurnstileClient::$calls);
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_missing_token_is_rejected_without_calling_cloudflare(): void
    {
        $this->enableTurnstile();

        $response = $this->sendContact();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, FakeTurnstileClient::$calls);
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_failed_verification_blocks_the_email(): void
    {
        $this->enableTurnstile();
        FakeTurnstileClient::$result = TurnstileResult::failure(['invalid-input-response'], 'localhost', 'contact', gmdate('c'));

        $response = $this->sendContact(['turnstileToken' => 'bad-token']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_timeout_or_transport_error_fails_closed(): void
    {
        $this->enableTurnstile();
        FakeTurnstileClient::$result = TurnstileResult::transportError(['http_status' => 504]);

        $response = $this->sendContact(['turnstileToken' => 'timeout-token']);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_hostname_mismatch_is_rejected(): void
    {
        $this->enableTurnstile();
        FakeTurnstileClient::$result = TurnstileResult::success('evil.example', 'contact', gmdate('c'));

        $response = $this->sendContact(['turnstileToken' => 'hostname-token']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_action_mismatch_is_rejected(): void
    {
        $this->enableTurnstile();
        FakeTurnstileClient::$result = TurnstileResult::success('localhost', 'login', gmdate('c'));

        $response = $this->sendContact(['turnstileToken' => 'action-token']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->enableTurnstile();
        FakeTurnstileClient::$result = TurnstileResult::success('localhost', 'contact', gmdate('c', time() - 3600));

        $response = $this->sendContact(['turnstileToken' => 'expired-token']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_token_cannot_be_replayed(): void
    {
        $this->enableTurnstile();

        $first = $this->sendContact(['turnstileToken' => 'replay-token']);
        $second = $this->sendContact([
            'turnstileToken' => 'replay-token',
            'message' => 'A different message that should still be blocked.',
        ]);

        $this->assertSame(201, $first->getStatusCode());
        $this->assertSame(422, $second->getStatusCode());
        $this->assertSame('turnstile_duplicate', $this->responseErrors($second)[0]['code'] ?? null);
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_disabled_turnstile_does_not_require_a_token(): void
    {
        $response = $this->sendContact();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertCount(1, CapturingTransport::$messages);
    }
}
