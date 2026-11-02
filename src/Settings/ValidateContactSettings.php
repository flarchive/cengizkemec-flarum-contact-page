<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Settings;

use Cengizkemec\ContactPage\Support\Defaults;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Intercepts Flarum's settings save event and validates only this extension's
 * keys. Other extensions and core settings are left untouched.
 */
final class ValidateContactSettings
{
    public function __construct(
        private readonly ContactSettingsValidator $validator,
        private readonly SettingsRepositoryInterface $settings
    ) {
    }

    public function handle(Saving $event): void
    {
        $dirty = [];

        foreach (array_keys($event->settings) as $key) {
            if (str_starts_with($key, Defaults::PREFIX)) {
                $dirty[$key] = $event->settings[$key];
            }
        }

        if ($dirty === []) {
            return;
        }

        $values = [];

        foreach (Defaults::all() as $name => $default) {
            $values[$name] = $this->settings->get(Defaults::key($name), $default);
        }

        foreach ($dirty as $key => $value) {
            $values[substr($key, strlen(Defaults::PREFIX))] = $value;
        }

        $normalized = $this->validator->validate($values);

        // Only write back keys the admin actually submitted; the settings
        // endpoint is responsible for the rest.
        foreach (array_keys($dirty) as $key) {
            $name = substr($key, strlen(Defaults::PREFIX));

            if (array_key_exists($name, $normalized)) {
                $event->settings[$key] = $normalized[$name];
            }
        }
    }
}
