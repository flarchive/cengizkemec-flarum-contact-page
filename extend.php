<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Cengizkemec\ContactPage\Api\Controller\SubmitContactController;
use Cengizkemec\ContactPage\ContactPageServiceProvider;
use Cengizkemec\ContactPage\Content\ContactPageContent;
use Cengizkemec\ContactPage\Http\Middleware\EnforceContactRequestSize;
use Cengizkemec\ContactPage\Settings\ValidateContactSettings;
use Cengizkemec\ContactPage\Support\Defaults;
use Cengizkemec\ContactPage\Support\ForumConfig;
use Flarum\Extend;
use Flarum\Http\Middleware\ParseJsonBody;
use Flarum\Settings\Event\Saving;

$settings = new Extend\Settings();

foreach (Defaults::all() as $name => $value) {
    $settings->default(Defaults::key($name), $value);
}

$settings->serializeToForum('contactPageConfig', Defaults::key('enabled'), ForumConfig::class);

return [
    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/contact', 'cengizkemec-contact-page.contact', ContactPageContent::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Routes('api'))
        ->post('/contact', 'cengizkemec-contact-page.submit', SubmitContactController::class),

    (new Extend\Middleware('api'))
        ->insertBefore(ParseJsonBody::class, EnforceContactRequestSize::class),

    (new Extend\View)
        ->namespace('contact-page', __DIR__.'/views'),

    $settings,

    (new Extend\Event)
        ->listen(Saving::class, ValidateContactSettings::class),

    (new Extend\ServiceProvider)
        ->register(ContactPageServiceProvider::class),
];
