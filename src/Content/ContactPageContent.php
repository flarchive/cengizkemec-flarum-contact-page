<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Content;

use Cengizkemec\ContactPage\Support\ContactSettings;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Adds a meaningful server-rendered title, description and canonical URL to
 * the contact page for search engines and no-JS clients.
 */
final class ContactPageContent
{
    public function __construct(
        private readonly ContactSettings $settings,
        private readonly TranslatorInterface $translator,
        private readonly UrlGenerator $url
    ) {
    }

    public function __invoke(Document $document, ServerRequestInterface $request): void
    {
        $title = $this->settings->string('page_title');
        $description = trim($this->settings->string('page_description'));

        $document->title = $title !== ''
            ? $title
            : $this->translator->trans('cengizkemec-contact-page.forum.page_title');

        if ($description !== '') {
            $document->meta['description'] = $description;
        }

        $document->canonicalUrl = $this->url->to('forum')->route('cengizkemec-contact-page.contact');

        if (! $this->settings->enabled()) {
            $document->meta['robots'] = 'noindex, nofollow';
        }
    }
}
