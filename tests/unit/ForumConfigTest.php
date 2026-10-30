<?php

namespace Cengizkemec\ContactPage\Tests\unit;

use Cengizkemec\ContactPage\Support\ContactSettings;
use Cengizkemec\ContactPage\Support\ForumConfig;
use Cengizkemec\ContactPage\Tests\Support\SettingsMock;
use PHPUnit\Framework\TestCase;

class ForumConfigTest extends TestCase
{
    public function test_private_settings_are_not_serialized_to_the_forum(): void
    {
        $settings = new ContactSettings(SettingsMock::make([
            'enabled' => true,
            'recipient_email' => 'secret-recipient@example.com',
            'turnstile_enabled' => true,
            'turnstile_site_key' => 'public-site-key',
            'turnstile_secret_key' => 'super-secret-key',
            'turnstile_expected_hostname' => 'internal.example',
            'security_salt' => str_repeat('a', 64),
            'rate_limit_max' => 99,
            'honeypot_enabled' => true,
            'time_trap_seconds' => 9,
            'max_request_bytes' => 12345,
        ]));

        $config = (new ForumConfig($settings))(true);
        $json = json_encode($config);

        $this->assertSame('public-site-key', $config['turnstile']['siteKey']);
        $this->assertStringNotContainsString('secret-recipient@example.com', $json);
        $this->assertStringNotContainsString('super-secret-key', $json);
        $this->assertStringNotContainsString('internal.example', $json);
        $this->assertStringNotContainsString(str_repeat('a', 64), $json);
        $this->assertStringNotContainsString('rate_limit_max', $json);
        $this->assertStringNotContainsString('12345', $json);
    }

    public function test_access_and_field_configuration_is_public(): void
    {
        $settings = new ContactSettings(SettingsMock::make([
            'enabled' => true,
            'access_mode' => 'registered',
            'categories' => "General\nSupport",
            'field_category_enabled' => true,
            'field_category_required' => true,
        ]));

        $config = (new ForumConfig($settings))(true);

        $this->assertTrue($config['enabled']);
        $this->assertSame('registered', $config['accessMode']);
        $this->assertSame(['General', 'Support'], $config['categories']);
        $this->assertTrue($config['fields']['category']['enabled']);
        $this->assertTrue($config['fields']['category']['required']);
    }

    public function test_disabled_access_mode_makes_the_page_unavailable(): void
    {
        $settings = new ContactSettings(SettingsMock::make([
            'enabled' => true,
            'access_mode' => 'disabled',
        ]));

        $config = (new ForumConfig($settings))(true);

        $this->assertFalse($config['enabled']);
    }
}
