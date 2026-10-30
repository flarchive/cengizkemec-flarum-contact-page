<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Support;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Typed, read-only access to this extension's settings.
 */
final class ContactSettings
{
    /**
     * @var array<string, mixed>
     */
    private array $defaults;

    public function __construct(
        private readonly SettingsRepositoryInterface $settings
    ) {
        $this->defaults = Defaults::all();
    }

    public function raw(string $name): mixed
    {
        return $this->settings->get(Defaults::key($name), $this->defaults[$name] ?? null);
    }

    public function bool(string $name): bool
    {
        $value = $this->raw($name);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    public function int(string $name): int
    {
        $value = $this->raw($name);

        return is_numeric($value) ? (int) $value : (int) ($this->defaults[$name] ?? 0);
    }

    public function string(string $name): string
    {
        $value = $this->raw($name);

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * @return string[]
     */
    public function categories(): array
    {
        $raw = $this->string('categories');

        if ($raw === '') {
            return [];
        }

        $categories = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim($line);

            if ($line !== '' && ! in_array($line, $categories, true)) {
                $categories[] = $line;
            }
        }

        return $categories;
    }

    /**
     * @return array{enabled: bool, required: bool, label: string, placeholder: string}
     */
    public function field(string $field): array
    {
        $prefix = 'field_'.$field;

        return [
            'enabled' => $this->bool($prefix.'_enabled'),
            'required' => $this->bool($prefix.'_required'),
            'label' => $this->string($prefix.'_label'),
            'placeholder' => $this->string($prefix.'_placeholder'),
        ];
    }

    public function enabled(): bool
    {
        return $this->bool('enabled') && $this->string('access_mode') !== 'disabled';
    }

    public function recipientEmail(): string
    {
        return $this->string('recipient_email');
    }

    public function turnstileEnabled(): bool
    {
        return $this->bool('turnstile_enabled');
    }

    public function turnstileSiteKey(): string
    {
        return $this->string('turnstile_site_key');
    }

    public function turnstileSecretKey(): string
    {
        return $this->string('turnstile_secret_key');
    }

    public function turnstileTimeout(): int
    {
        $timeout = $this->int('turnstile_timeout');

        return max(1, min(30, $timeout));
    }

    public function rateLimitEnabled(): bool
    {
        return $this->bool('rate_limit_enabled');
    }

    public function rateLimitMax(): int
    {
        return max(1, $this->int('rate_limit_max'));
    }

    public function rateLimitWindow(): int
    {
        return max(1, $this->int('rate_limit_window'));
    }

    public function cooldownSeconds(): int
    {
        return max(0, $this->int('cooldown_seconds'));
    }

    public function messageMinLength(): int
    {
        return max(1, $this->int('message_min_length'));
    }

    public function messageMaxLength(): int
    {
        return max($this->messageMinLength(), $this->int('message_max_length'));
    }

    public function subjectMaxLength(): int
    {
        return max(1, $this->int('subject_max_length'));
    }

    public function duplicateWindowSeconds(): int
    {
        return max(0, $this->int('duplicate_window_seconds'));
    }

    public function maxRequestBytes(): int
    {
        return max(1024, $this->int('max_request_bytes'));
    }

    public function timeTrapSeconds(): int
    {
        return max(0, $this->int('time_trap_seconds'));
    }

    public function privacyUrl(): string
    {
        $url = $this->string('privacy_url');

        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
}
