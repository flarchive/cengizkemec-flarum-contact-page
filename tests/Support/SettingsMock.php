<?php

namespace Cengizkemec\ContactPage\Tests\Support;

use Cengizkemec\ContactPage\Support\Defaults;
use Flarum\Settings\SettingsRepositoryInterface;
use Mockery;

final class SettingsMock
{
    /**
     * @param array<string, mixed> $overrides Unprefixed setting values.
     */
    public static function make(array $overrides = []): SettingsRepositoryInterface
    {
        $values = array_merge(Defaults::all(), $overrides);

        $repository = Mockery::mock(SettingsRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturnUsing(function (string $key, mixed $default = null) use ($values) {
            $name = str_starts_with($key, Defaults::PREFIX) ? substr($key, strlen(Defaults::PREFIX)) : $key;

            return array_key_exists($name, $values) ? $values[$name] : $default;
        });

        return $repository;
    }
}
