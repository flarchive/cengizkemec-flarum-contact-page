<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage;

use Cengizkemec\ContactPage\Services\Turnstile\CloudflareTurnstileClient;
use Cengizkemec\ContactPage\Services\Turnstile\TurnstileClientInterface;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Locale\LocaleManager;

final class ContactPageServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Binding the interface keeps the network client swappable in tests and
        // avoids constructing an HTTP client during container boot.
        $this->container->singleton(TurnstileClientInterface::class, CloudflareTurnstileClient::class);
    }

    public function boot(LocaleManager $locales): void
    {
        // `Extend\Locales` registers files through a resolving callback. In
        // long-lived processes and in the test harness the locale manager can
        // already be resolved by the time extensions are extended, so register
        // the same files here as a deterministic fallback. Duplicate resource
        // registration is harmless.
        foreach (glob(__DIR__.'/../locale/*.yml') ?: [] as $file) {
            $locales->addTranslations(basename($file, '.yml'), $file);
        }
    }
}
