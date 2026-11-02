<?php

namespace Cengizkemec\ContactPage\Tests\unit;

use Cengizkemec\ContactPage\Services\BotDetector;
use Cengizkemec\ContactPage\Support\ContactSettings;
use Cengizkemec\ContactPage\Tests\Support\SettingsMock;
use PHPUnit\Framework\TestCase;

class BotDetectorTest extends TestCase
{
    public function test_honeypot_field_detects_a_bot(): void
    {
        $detector = $this->detector(['honeypot_enabled' => true]);

        $this->assertSame('honeypot', $detector->reason(['contact_hp' => 'spam', 'elapsedMs' => 5000]));
    }

    public function test_time_trap_detects_fast_missing_and_stale_forms(): void
    {
        $detector = $this->detector([
            'honeypot_enabled' => false,
            'time_trap_enabled' => true,
            'time_trap_seconds' => 2,
        ]);

        $this->assertSame('time_trap', $detector->reason(['elapsedMs' => 100]));
        $this->assertSame('time_trap_missing', $detector->reason([]));
        $this->assertSame('time_trap', $detector->reason(['elapsedMs' => 7 * 60 * 60 * 1000]));
        $this->assertNull($detector->reason(['elapsedMs' => 5000]));
    }

    public function test_disabled_checks_allow_the_submission(): void
    {
        $detector = $this->detector([
            'honeypot_enabled' => false,
            'time_trap_enabled' => false,
        ]);

        $this->assertNull($detector->reason(['contact_hp' => 'spam']));
    }

    private function detector(array $overrides): BotDetector
    {
        return new BotDetector(new ContactSettings(SettingsMock::make($overrides)));
    }
}
