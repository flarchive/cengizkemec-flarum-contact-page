<?php

namespace Cengizkemec\ContactPage\Tests\integration;

use Cengizkemec\ContactPage\Support\Defaults;

class SettingsValidationTest extends ContactPageTestCase
{
    /**
     * Saving settings writes one row per changed key by design; this is not an
     * N+1 relationship query.
     */
    protected function allowedRepeatedQueries(): array
    {
        return ['insert into "settings"'];
    }

    public function test_admin_can_save_valid_settings_and_values_are_normalized(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    Defaults::key('enabled') => '1',
                    Defaults::key('recipient_email') => 'admin@example.com',
                    Defaults::key('categories') => "General\n\nSupport\nGeneral\n",
                    Defaults::key('message_min_length') => '20',
                    Defaults::key('message_max_length') => '2000',
                ],
            ])
        );

        $this->assertSame(204, $response->getStatusCode());

        $settings = $this->database()->table('settings')->pluck('value', 'key');

        $this->assertSame('1', (string) $settings[Defaults::key('enabled')]);
        $this->assertSame("General\nSupport", (string) $settings[Defaults::key('categories')]);
        $this->assertSame('20', (string) $settings[Defaults::key('message_min_length')]);
        $this->assertSame('2000', (string) $settings[Defaults::key('message_max_length')]);
    }

    public function test_message_length_range_is_validated(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    Defaults::key('message_min_length') => '100',
                    Defaults::key('message_max_length') => '10',
                ],
            ])
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('minimum message length', strtolower($this->detail($response)));
    }

    public function test_turnstile_requires_both_keys_when_enabled(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    Defaults::key('turnstile_enabled') => '1',
                    Defaults::key('turnstile_site_key') => '',
                    Defaults::key('turnstile_secret_key') => '',
                ],
            ])
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('turnstile', strtolower($this->detail($response)));
    }

    public function test_enabled_page_requires_a_valid_recipient(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    Defaults::key('enabled') => '1',
                    Defaults::key('recipient_email') => '',
                ],
            ])
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('recipient', strtolower($this->detail($response)));
    }

    public function test_invalid_enum_and_url_are_rejected(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    Defaults::key('access_mode') => 'nobody',
                    Defaults::key('privacy_url') => 'javascript:alert(1)',
                ],
            ])
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_non_admins_cannot_change_settings(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 2,
                'json' => [
                    Defaults::key('enabled') => '0',
                ],
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    private function detail($response): string
    {
        $errors = $this->responseErrors($response);

        return (string) ($errors[0]['detail'] ?? '');
    }
}
