<?php

namespace Cengizkemec\ContactPage\Tests\integration;

use Cengizkemec\ContactPage\Services\Turnstile\TurnstileClientInterface;
use Cengizkemec\ContactPage\Support\Defaults;
use Cengizkemec\ContactPage\Tests\Support\CapturingMailDriver;
use Cengizkemec\ContactPage\Tests\Support\CapturingTransport;
use Cengizkemec\ContactPage\Tests\Support\FakeTurnstileClient;
use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mime\Email;

abstract class ContactPageTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        CapturingMailDriver::reset();
        FakeTurnstileClient::reset();

        $this->setting('mail_driver', 'contact-page-test');
        $this->setting('mail_from', 'noreply@example.com');
        $this->setting('mail_format', 'multipart');

        $this->contactSettings([
            'enabled' => true,
            'recipient_email' => 'admin@example.com',
            'email_subject_prefix' => '[Contact]',
            'access_mode' => 'everyone',
            'allow_guest_submissions' => true,
            'allow_registered_submissions' => true,
            'require_email_verification' => false,
            'honeypot_enabled' => false,
            'time_trap_enabled' => false,
            'rate_limit_enabled' => false,
            'duplicate_window_seconds' => 0,
            'turnstile_enabled' => false,
        ]);

        $this->extension('cengizkemec-contact-page');

        $this->extend(
            (new Extend\Mail())->driver('contact-page-test', CapturingMailDriver::class)
        );

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function contactSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->setting(Defaults::key($key), $value);
        }
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'A question',
            'message' => 'This is a complete and valid contact message.',
            'privacy' => '1',
            'elapsedMs' => 5000,
            'contact_hp' => '',
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function sendContact(array $overrides = [], ?int $userId = null, bool $withCsrf = true): ResponseInterface
    {
        $payload = $this->payload($overrides);
        $options = ['json' => $payload];

        if ($userId !== null) {
            $options['authenticatedAs'] = $userId;
        }

        $request = $this->request('POST', '/api/contact', $options);

        if ($userId === null && $withCsrf) {
            $request = $this->requestWithCsrfToken($request);
        }

        return $this->send($request);
    }

    protected function bindFakeTurnstile(): void
    {
        $this->app()->getContainer()->instance(TurnstileClientInterface::class, new FakeTurnstileClient());
    }

    protected function lastMail(): ?Email
    {
        $messages = CapturingTransport::$messages;

        return $messages === [] ? null : $messages[array_key_last($messages)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function responseErrors(ResponseInterface $response): array
    {
        $body = json_decode((string) $response->getBody(), true);

        return is_array($body['errors'] ?? null) ? $body['errors'] : [];
    }
}
