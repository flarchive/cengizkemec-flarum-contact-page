<?php

namespace Cengizkemec\ContactPage\Tests\unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class LocalizationTest extends TestCase
{
    public function test_english_and_turkish_translation_files_have_identical_keys(): void
    {
        $en = $this->flatten(Yaml::parseFile(__DIR__.'/../../locale/en.yml'));
        $tr = $this->flatten(Yaml::parseFile(__DIR__.'/../../locale/tr.yml'));

        $this->assertSame([], array_keys(array_diff_key($en, $tr)), 'Keys missing from tr.yml');
        $this->assertSame([], array_keys(array_diff_key($tr, $en)), 'Keys missing from en.yml');
        $this->assertGreaterThan(150, count($en));
    }

    public function test_no_translation_value_is_empty(): void
    {
        foreach (['en', 'tr'] as $locale) {
            $translations = $this->flatten(Yaml::parseFile(__DIR__."/../../locale/$locale.yml"));

            foreach ($translations as $key => $value) {
                $this->assertIsString($value, "$locale.$key must be a string");
                $this->assertNotSame('', trim($value), "$locale.$key must not be empty");
            }
        }
    }

    public function test_required_user_facing_keys_are_present(): void
    {
        $en = $this->flatten(Yaml::parseFile(__DIR__.'/../../locale/en.yml'));
        $tr = $this->flatten(Yaml::parseFile(__DIR__.'/../../locale/tr.yml'));

        foreach ([
            'cengizkemec-contact-page.forum.nav',
            'cengizkemec-contact-page.forum.page_title',
            'cengizkemec-contact-page.forum.fields.name_label',
            'cengizkemec-contact-page.forum.validation.email_invalid',
            'cengizkemec-contact-page.forum.errors.turnstile_failed',
            'cengizkemec-contact-page.admin.sections.security',
            'cengizkemec-contact-page.admin.validation.turnstile_keys',
            'cengizkemec-contact-page.email.heading',
        ] as $key) {
            $this->assertArrayHasKey($key, $en);
            $this->assertArrayHasKey($key, $tr);
        }
    }

    /**
     * @return array<string, string>
     */
    private function flatten(array $data, string $prefix = ''): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $out += $this->flatten($value, $full);
            } else {
                $out[$full] = $value;
            }
        }

        return $out;
    }
}
