<?php

namespace Cengizkemec\ContactPage\Tests\integration;

class ContactPageRouteTest extends ContactPageTestCase
{
    public function test_contact_page_route_returns_the_frontend(): void
    {
        $response = $this->send($this->request('GET', '/contact'));

        $this->assertSame(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertStringContainsStringIgnoringCase('<!doctype html>', $body);
        $this->assertStringContainsString('Contact', $body);
    }

    public function test_guest_post_without_csrf_token_is_rejected(): void
    {
        $response = $this->send($this->request('POST', '/api/contact', ['json' => $this->payload()]));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([], \Cengizkemec\ContactPage\Tests\Support\CapturingTransport::$messages);
    }

    public function test_custom_title_description_and_disabled_page_seo(): void
    {
        $this->contactSettings([
            'enabled' => false,
            'page_title' => 'Support desk',
            'page_description' => 'We are here to help.',
        ]);

        $response = $this->send($this->request('GET', '/contact'));
        $body = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<title>Support desk', $body);
        $this->assertStringContainsString('name="description" content="We are here to help."', $body);
        $this->assertStringContainsString('name="robots" content="noindex, nofollow"', $body);
    }
}
