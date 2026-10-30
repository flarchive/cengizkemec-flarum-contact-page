<?php

namespace Cengizkemec\ContactPage\Tests\integration;

use Cengizkemec\ContactPage\Tests\Support\CapturingMailDriver;
use Cengizkemec\ContactPage\Tests\Support\CapturingTransport;

class MailDeliveryTest extends ContactPageTestCase
{
    public function test_message_is_sent_through_flarums_configured_mailer(): void
    {
        $this->contactSettings([
            'field_category_enabled' => true,
            'categories' => "General\nSupport",
            'sender_name' => 'Forum Support',
        ]);

        $response = $this->sendContact([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Account question',
            'message' => "First line.\nSecond line.",
            'category' => 'Support',
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertCount(1, CapturingTransport::$messages);

        $mail = $this->lastMail();

        $this->assertNotNull($mail);
        $this->assertSame('admin@example.com', $mail->getTo()[0]->getAddress());
        $this->assertSame('noreply@example.com', $mail->getFrom()[0]->getAddress());
        $this->assertSame('Forum Support', $mail->getFrom()[0]->getName());
        $this->assertSame('jane@example.com', $mail->getReplyTo()[0]->getAddress());
        $this->assertSame('[Contact] Account question', $mail->getSubject());

        $html = (string) $mail->getHtmlBody();
        $text = (string) $mail->getTextBody();

        $this->assertStringContainsString('Jane Doe', $html);
        $this->assertStringContainsString('jane@example.com', $html);
        $this->assertStringContainsString('First line.', $html);
        $this->assertStringContainsString('Second line.', $html);
        $this->assertStringContainsString('Support', $html);
        $this->assertStringContainsString('First line.', $text);
        $this->assertStringContainsString('Support', $text);
    }

    public function test_reply_to_can_be_disabled(): void
    {
        $this->contactSettings(['reply_to_enabled' => false]);

        $this->sendContact();

        $mail = $this->lastMail();
        $this->assertNotNull($mail);
        $this->assertSame([], $mail->getReplyTo());
    }

    public function test_transport_failure_is_reported_and_releases_the_guard(): void
    {
        $this->contactSettings([
            'duplicate_window_seconds' => 300,
            'rate_limit_enabled' => true,
            'rate_limit_max' => 5,
            'rate_limit_window' => 600,
            'cooldown_seconds' => 60,
        ]);

        CapturingMailDriver::$fail = true;

        $failed = $this->sendContact();
        $this->assertSame(503, $failed->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);

        CapturingMailDriver::$fail = false;

        $retried = $this->sendContact();
        $this->assertSame(201, $retried->getStatusCode());
        $this->assertCount(1, CapturingTransport::$messages);
    }

    public function test_mail_driver_that_cannot_send_is_treated_as_unavailable(): void
    {
        CapturingMailDriver::$canSend = false;

        $response = $this->sendContact();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('mail_unavailable', $this->responseErrors($response)[0]['code'] ?? null);
        $this->assertCount(0, CapturingTransport::$messages);
    }

    public function test_missing_or_invalid_recipient_is_treated_as_unavailable(): void
    {
        $this->contactSettings(['recipient_email' => '']);

        $response = $this->sendContact();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertCount(0, CapturingTransport::$messages);
    }
}
