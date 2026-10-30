<?php

namespace Cengizkemec\ContactPage\Tests\Support;

use Flarum\Mail\DriverInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class CapturingMailDriver implements DriverInterface
{
    public static bool $canSend = true;
    public static bool $fail = false;

    public static function reset(): void
    {
        self::$canSend = true;
        self::$fail = false;
        CapturingTransport::reset();
    }

    public function availableSettings(): array
    {
        return [];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return new MessageBag();
    }

    public function canSend(): bool
    {
        return self::$canSend;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): TransportInterface
    {
        return new CapturingTransport();
    }
}
