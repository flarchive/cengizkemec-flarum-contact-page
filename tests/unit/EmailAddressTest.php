<?php

namespace Cengizkemec\ContactPage\Tests\unit;

use Cengizkemec\ContactPage\Support\EmailAddress;
use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    public function test_valid_addresses_are_accepted(): void
    {
        $this->assertTrue(EmailAddress::isValid('person@example.com'));
        $this->assertTrue(EmailAddress::isValid('person+tag@sub.example.co.uk'));
        $this->assertTrue(EmailAddress::isValid('noreply@localhost'));
        $this->assertFalse(EmailAddress::isValid('noreply@localhost', true));
    }

    public function test_invalid_addresses_are_rejected(): void
    {
        $this->assertFalse(EmailAddress::isValid(''));
        $this->assertFalse(EmailAddress::isValid('not-an-email'));
        $this->assertFalse(EmailAddress::isValid('two@@example.com'));
        $this->assertFalse(EmailAddress::isValid(str_repeat('a', 250).'@example.com'));
    }

    public function test_display_names_and_header_injection_are_rejected(): void
    {
        $this->assertFalse(EmailAddress::isValid('Name <person@example.com>'));
        $this->assertFalse(EmailAddress::isValid("person@example.com\r\nBcc: victim@example.com"));
        $this->assertFalse(EmailAddress::isValid("person@example.com\nBcc: victim@example.com"));
    }

    public function test_normalize_returns_null_for_invalid_values(): void
    {
        $this->assertSame('person@example.com', EmailAddress::normalize(' person@example.com '));
        $this->assertNull(EmailAddress::normalize(['person@example.com']));
        $this->assertNull(EmailAddress::normalize('not-an-email'));
    }
}
