<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Settings;

use Cengizkemec\ContactPage\Support\Defaults;
use Cengizkemec\ContactPage\Support\EmailAddress;
use Cengizkemec\ContactPage\Support\Text;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;

/**
 * Validates and normalizes every admin-editable setting.
 *
 * Flarum's generic settings endpoint only enforces a maximum length, so this
 * validator is the backend authority for ranges, enums, URLs and cross-field
 * rules. It also normalizes values before they reach the settings table.
 */
final class ContactSettingsValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @param array<string, mixed> $values Unprefixed setting values.
     * @return array<string, mixed> Normalized values.
     */
    public function validate(array $values): array
    {
        $errors = [];
        $normalized = $values;

        foreach ($this->booleanKeys() as $key) {
            $value = $this->boolean($values[$key] ?? null);

            if ($value === null) {
                $errors[$this->key($key)] = $this->message('boolean', $key);
            } else {
                $normalized[$key] = $value ? '1' : '0';
            }
        }

        foreach ($this->integerRules() as $key => [$min, $max]) {
            $value = $values[$key] ?? null;

            if (! is_numeric($value) || (string) (int) $value !== (string) $value) {
                $errors[$this->key($key)] = $this->message('integer', $key);
                continue;
            }

            $value = (int) $value;
            if ($value < $min || $value > $max) {
                $errors[$this->key($key)] = $this->message('range', $key, ['min' => $min, 'max' => $max]);
                continue;
            }

            $normalized[$key] = $value;
        }

        foreach ($this->enumRules() as $key => $allowed) {
            $value = (string) ($values[$key] ?? '');

            if (! in_array($value, $allowed, true)) {
                $errors[$this->key($key)] = $this->message('enum', $key);
            } else {
                $normalized[$key] = $value;
            }
        }

        foreach ($this->singleLineKeys() as $key => $maxLength) {
            $value = Text::singleLine($values[$key] ?? '');
            $normalized[$key] = $value;

            if (Text::length($value) > $maxLength) {
                $errors[$this->key($key)] = $this->message('too_long', $key, ['max' => $maxLength]);
            }
        }

        foreach ($this->multilineKeys() as $key => $maxLength) {
            $value = Text::multiline($values[$key] ?? '');
            $normalized[$key] = $value;

            if (Text::length($value) > $maxLength) {
                $errors[$this->key($key)] = $this->message('too_long', $key, ['max' => $maxLength]);
            }
        }

        foreach (['turnstile_site_key', 'turnstile_secret_key'] as $key) {
            $value = trim((string) ($values[$key] ?? ''));
            $normalized[$key] = $value;

            if (preg_match('/\s/u', $value) || Text::length($value) > 255) {
                $errors[$this->key($key)] = $this->message('whitespace', $key);
            }
        }

        $recipient = trim((string) ($values['recipient_email'] ?? ''));
        $normalized['recipient_email'] = $recipient;

        if ($recipient !== '' && ! EmailAddress::isValid($recipient, true)) {
            $errors[$this->key('recipient_email')] = $this->message('email', 'recipient_email');
        }

        if ($this->boolean($normalized['enabled'] ?? false) && $recipient === '') {
            $errors[$this->key('recipient_email')] = $this->message('required', 'recipient_email');
        }

        $privacyUrl = trim((string) ($values['privacy_url'] ?? ''));
        $normalized['privacy_url'] = $privacyUrl;

        if ($privacyUrl !== '') {
            $scheme = strtolower((string) parse_url($privacyUrl, PHP_URL_SCHEME));

            if (! filter_var($privacyUrl, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
                $errors[$this->key('privacy_url')] = $this->message('url', 'privacy_url');
            }
        }

        $hostname = strtolower(trim((string) ($values['turnstile_expected_hostname'] ?? '')));
        $normalized['turnstile_expected_hostname'] = $hostname;

        if ($hostname !== '' && ! preg_match('/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?(?::\d{1,5})?$/', $hostname)) {
            $errors[$this->key('turnstile_expected_hostname')] = $this->message('hostname', 'turnstile_expected_hostname');
        }

        $normalized['categories'] = $this->normalizeCategories($values['categories'] ?? '', $errors);

        $messageMin = (int) ($normalized['message_min_length'] ?? 10);
        $messageMax = (int) ($normalized['message_max_length'] ?? 5000);

        if ($messageMin > $messageMax) {
            $errors[$this->key('message_min_length')] = $this->message('message_range', 'message_min_length');
        }

        if ($this->boolean($normalized['field_category_enabled'] ?? false) && $normalized['categories'] === '') {
            $errors[$this->key('categories')] = $this->message('categories_empty', 'categories');
        }

        if ($this->boolean($normalized['turnstile_enabled'] ?? false)) {
            if ($normalized['turnstile_site_key'] === '' || $normalized['turnstile_secret_key'] === '') {
                $errors[$this->key('turnstile_site_key')] = $this->message('turnstile_keys', 'turnstile_site_key');
            }
        }

        if ($this->boolean($normalized['enabled'] ?? false)) {
            $emailEnabled = $this->boolean($normalized['field_email_enabled'] ?? false);
            $messageEnabled = $this->boolean($normalized['field_message_enabled'] ?? false);

            if (! $emailEnabled && ! $messageEnabled) {
                $errors[$this->key('enabled')] = $this->message('no_content_field', 'enabled');
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $normalized;
    }

    /**
     * @param array<string, string> $errors
     */
    private function normalizeCategories(mixed $value, array &$errors): string
    {
        $lines = preg_split('/\R/u', Text::multiline($value)) ?: [];
        $categories = [];

        foreach ($lines as $line) {
            $line = Text::singleLine($line);

            if ($line === '' || in_array($line, $categories, true)) {
                continue;
            }

            if (Text::length($line) > 100) {
                $errors[$this->key('categories')] = $this->message('category_too_long', 'categories');
                continue;
            }

            $categories[] = $line;
        }

        if (count($categories) > 50) {
            $errors[$this->key('categories')] = $this->message('categories_limit', 'categories');
        }

        return implode("\n", $categories);
    }

    private function boolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        if (in_array($value, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'off', 'no', ''], true)) {
            return false;
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function booleanKeys(): array
    {
        return [
            'enabled',
            'show_in_navigation',
            'allow_guest_submissions',
            'allow_registered_submissions',
            'require_email_verification',
            'prefill_authenticated',
            'field_name_enabled',
            'field_name_required',
            'field_email_enabled',
            'field_email_required',
            'field_subject_enabled',
            'field_subject_required',
            'field_message_enabled',
            'field_message_required',
            'field_category_enabled',
            'field_category_required',
            'field_phone_enabled',
            'field_phone_required',
            'field_privacy_enabled',
            'field_privacy_required',
            'reply_to_enabled',
            'turnstile_enabled',
            'rate_limit_enabled',
            'honeypot_enabled',
            'time_trap_enabled',
        ];
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    private function integerRules(): array
    {
        return [
            'message_min_length' => [1, 50000],
            'message_max_length' => [1, 50000],
            'subject_max_length' => [1, 500],
            'turnstile_timeout' => [1, 30],
            'rate_limit_max' => [1, 1000],
            'rate_limit_window' => [10, 86400],
            'cooldown_seconds' => [0, 3600],
            'time_trap_seconds' => [0, 60],
            'duplicate_window_seconds' => [0, 86400],
            'max_request_bytes' => [1024, 1048576],
        ];
    }

    /**
     * @return array<string, string[]>
     */
    private function enumRules(): array
    {
        return [
            'default_language' => ['auto', 'en', 'tr'],
            'access_mode' => ['everyone', 'registered', 'guests', 'disabled'],
            'turnstile_theme' => ['auto', 'light', 'dark'],
            'turnstile_appearance' => ['always', 'interaction-only'],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function singleLineKeys(): array
    {
        return [
            'nav_label' => 100,
            'page_title' => 200,
            'email_subject_prefix' => 100,
            'sender_name' => 100,
            'field_name_label' => 100,
            'field_name_placeholder' => 200,
            'field_email_label' => 100,
            'field_email_placeholder' => 200,
            'field_subject_label' => 100,
            'field_subject_placeholder' => 200,
            'field_message_label' => 100,
            'field_message_placeholder' => 200,
            'field_category_label' => 100,
            'field_category_placeholder' => 200,
            'field_phone_label' => 100,
            'field_phone_placeholder' => 200,
            'field_privacy_label' => 100,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function multilineKeys(): array
    {
        return [
            'page_description' => 2000,
            'success_message' => 2000,
        ];
    }

    private function key(string $name): string
    {
        return Defaults::key($name);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function message(string $type, string $key, array $params = []): string
    {
        return $this->translator->trans(
            'cengizkemec-contact-page.admin.validation.'.$type,
            array_merge(['field' => $this->label($key)], $params)
        );
    }

    private function label(string $key): string
    {
        $translationKey = 'cengizkemec-contact-page.admin.settings.'.$key.'_label';

        return $this->translator->trans($translationKey);
    }
}
