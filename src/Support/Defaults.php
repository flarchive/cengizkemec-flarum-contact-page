<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Support;

/**
 * Central list of settings and their defaults.
 *
 * Keeping the defaults in one place means the PHP settings repository, the
 * admin form and the server-side validators can never drift apart.
 */
final class Defaults
{
    public const PREFIX = 'cengizkemec-contact-page.';

    public static function key(string $name): string
    {
        return self::PREFIX.$name;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return [
            'enabled' => false,
            'show_in_navigation' => true,
            'nav_label' => '',
            'page_title' => '',
            'page_description' => '',
            'success_message' => '',
            'categories' => '',
            'default_language' => 'auto',

            'access_mode' => 'everyone',
            'allow_guest_submissions' => true,
            'allow_registered_submissions' => true,
            'require_email_verification' => false,
            'prefill_authenticated' => true,

            'field_name_enabled' => true,
            'field_name_required' => true,
            'field_name_label' => '',
            'field_name_placeholder' => '',

            'field_email_enabled' => true,
            'field_email_required' => true,
            'field_email_label' => '',
            'field_email_placeholder' => '',

            'field_subject_enabled' => true,
            'field_subject_required' => true,
            'field_subject_label' => '',
            'field_subject_placeholder' => '',

            'field_message_enabled' => true,
            'field_message_required' => true,
            'field_message_label' => '',
            'field_message_placeholder' => '',

            'field_category_enabled' => false,
            'field_category_required' => false,
            'field_category_label' => '',
            'field_category_placeholder' => '',

            'field_phone_enabled' => false,
            'field_phone_required' => false,
            'field_phone_label' => '',
            'field_phone_placeholder' => '',

            'field_privacy_enabled' => true,
            'field_privacy_required' => true,
            'field_privacy_label' => '',
            'privacy_url' => '',

            'message_min_length' => 10,
            'message_max_length' => 5000,
            'subject_max_length' => 150,

            'recipient_email' => '',
            'email_subject_prefix' => '',
            'sender_name' => '',
            'reply_to_enabled' => true,

            'turnstile_enabled' => false,
            'turnstile_site_key' => '',
            'turnstile_secret_key' => '',
            'turnstile_theme' => 'auto',
            'turnstile_appearance' => 'always',
            'turnstile_timeout' => 5,
            'turnstile_expected_hostname' => '',

            'rate_limit_enabled' => true,
            'rate_limit_max' => 5,
            'rate_limit_window' => 600,
            'cooldown_seconds' => 30,
            'honeypot_enabled' => true,
            'time_trap_enabled' => true,
            'time_trap_seconds' => 2,
            'duplicate_window_seconds' => 300,
            'max_request_bytes' => 65536,

            // Not exposed anywhere. Generated on first use and used to HMAC
            // IP addresses, duplicate fingerprints and Turnstile tokens.
            'security_salt' => '',
        ];
    }

    /**
     * Settings that must never be serialized to the forum frontend.
     *
     * @return string[]
     */
    public static function privateKeys(): array
    {
        return [
            'recipient_email',
            'email_subject_prefix',
            'sender_name',
            'reply_to_enabled',
            'turnstile_secret_key',
            'turnstile_expected_hostname',
            'rate_limit_enabled',
            'rate_limit_max',
            'rate_limit_window',
            'cooldown_seconds',
            'honeypot_enabled',
            'time_trap_enabled',
            'time_trap_seconds',
            'duplicate_window_seconds',
            'max_request_bytes',
            'security_salt',
        ];
    }
}
