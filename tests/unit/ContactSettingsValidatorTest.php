<?php

namespace Cengizkemec\ContactPage\Tests\unit;

use Cengizkemec\ContactPage\Settings\ContactSettingsValidator;
use Cengizkemec\ContactPage\Support\Defaults;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class ContactSettingsValidatorTest extends TestCase
{
    public function test_default_settings_are_valid_and_normalized(): void
    {
        $normalized = $this->validator()->validate(Defaults::all());

        $this->assertSame('0', $normalized['enabled']);
        $this->assertSame('1', $normalized['show_in_navigation']);
        $this->assertSame('everyone', $normalized['access_mode']);
        $this->assertSame(10, $normalized['message_min_length']);
    }

    public function test_enabling_requires_a_recipient(): void
    {
        $values = Defaults::all();
        $values['enabled'] = true;
        $values['recipient_email'] = '';

        $this->assertValidationError($values, 'recipient_email');
    }

    public function test_turnstile_requires_keys_when_enabled(): void
    {
        $values = Defaults::all();
        $values['turnstile_enabled'] = true;
        $values['turnstile_site_key'] = '';
        $values['turnstile_secret_key'] = '';

        $this->assertValidationError($values, 'turnstile_site_key');
    }

    public function test_category_field_requires_at_least_one_category(): void
    {
        $values = Defaults::all();
        $values['field_category_enabled'] = true;
        $values['categories'] = '';

        $this->assertValidationError($values, 'categories');
    }

    public function test_invalid_enum_url_and_range_are_rejected(): void
    {
        $values = Defaults::all();
        $values['access_mode'] = 'nobody';
        $values['privacy_url'] = 'javascript:alert(1)';
        $values['message_min_length'] = 500;
        $values['message_max_length'] = 10;

        $attributes = $this->errors($values);

        $this->assertArrayHasKey(Defaults::key('access_mode'), $attributes);
        $this->assertArrayHasKey(Defaults::key('privacy_url'), $attributes);
        $this->assertArrayHasKey(Defaults::key('message_min_length'), $attributes);
    }

    public function test_categories_are_deduplicated_and_trimmed(): void
    {
        $values = Defaults::all();
        $values['categories'] = " General \n\nSupport\nGeneral\n";

        $normalized = $this->validator()->validate($values);

        $this->assertSame("General\nSupport", $normalized['categories']);
    }

    public function test_secret_keys_may_not_contain_whitespace(): void
    {
        $values = Defaults::all();
        $values['turnstile_secret_key'] = 'secret with spaces';

        $this->assertValidationError($values, 'turnstile_secret_key');
    }

    private function validator(): ContactSettingsValidator
    {
        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturnUsing(fn (string $key, array $params = []) => $key);

        return new ContactSettingsValidator($translator);
    }

    private function assertValidationError(array $values, string $key): void
    {
        $attributes = $this->errors($values);

        $this->assertArrayHasKey(Defaults::key($key), $attributes);
    }

    /**
     * @return array<string, string>
     */
    private function errors(array $values): array
    {
        try {
            $this->validator()->validate($values);
        } catch (ValidationException $e) {
            return $e->getAttributes();
        }

        $this->fail('Expected a ValidationException.');
    }
}
