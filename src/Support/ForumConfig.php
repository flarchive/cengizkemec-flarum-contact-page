<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Support;

/**
 * Whitelisted public configuration for the forum frontend.
 *
 * This is the only place extension settings are serialized to visitors. It
 * deliberately excludes the recipient address, the Turnstile secret, rate
 * limits, the honeypot configuration, the request size limit and the security
 * salt.
 */
final class ForumConfig
{
    public function __construct(
        private readonly ContactSettings $settings
    ) {
    }

    public function __invoke(mixed $value): array
    {
        $categories = $this->settings->categories();
        $categoryEnabled = $this->settings->field('category')['enabled'] && $categories !== [];

        return [
            'enabled' => $this->settings->enabled(),
            'showInNavigation' => $this->settings->bool('show_in_navigation'),
            'navLabel' => $this->stringOrNull('nav_label'),
            'title' => $this->stringOrNull('page_title'),
            'description' => $this->stringOrNull('page_description'),
            'successMessage' => $this->stringOrNull('success_message'),
            'accessMode' => $this->settings->string('access_mode'),
            'allowGuestSubmissions' => $this->settings->bool('allow_guest_submissions'),
            'allowRegisteredSubmissions' => $this->settings->bool('allow_registered_submissions'),
            'requireEmailVerification' => $this->settings->bool('require_email_verification'),
            'prefillAuthenticated' => $this->settings->bool('prefill_authenticated'),
            'categories' => $categories,
            'fields' => [
                'name' => $this->field('name'),
                'email' => $this->field('email'),
                'subject' => $this->field('subject'),
                'message' => $this->field('message'),
                'category' => [
                    'enabled' => $categoryEnabled,
                    'required' => $categoryEnabled && $this->settings->field('category')['required'],
                    'label' => $this->stringOrNull('field_category_label'),
                    'placeholder' => $this->stringOrNull('field_category_placeholder'),
                ],
                'phone' => $this->field('phone'),
                'privacy' => $this->field('privacy'),
            ],
            'messageMinLength' => $this->settings->messageMinLength(),
            'messageMaxLength' => $this->settings->messageMaxLength(),
            'subjectMaxLength' => $this->settings->subjectMaxLength(),
            'privacyUrl' => $this->settings->privacyUrl() ?: null,
            'turnstile' => [
                'enabled' => $this->settings->turnstileEnabled(),
                'siteKey' => $this->settings->turnstileSiteKey() ?: null,
                'theme' => $this->settings->string('turnstile_theme'),
                'appearance' => $this->settings->string('turnstile_appearance'),
            ],
        ];
    }

    /**
     * @return array{enabled: bool, required: bool, label: string|null, placeholder: string|null}
     */
    private function field(string $field): array
    {
        $settings = $this->settings->field($field);

        return [
            'enabled' => $settings['enabled'],
            'required' => $settings['enabled'] && $settings['required'],
            'label' => $settings['label'] !== '' ? $settings['label'] : null,
            'placeholder' => $settings['placeholder'] !== '' ? $settings['placeholder'] : null,
        ];
    }

    private function stringOrNull(string $key): ?string
    {
        $value = $this->settings->string($key);

        return $value !== '' ? $value : null;
    }
}
