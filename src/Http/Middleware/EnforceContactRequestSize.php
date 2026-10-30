<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Http\Middleware;

use Cengizkemec\ContactPage\Support\ContactSettings;
use Flarum\Api\JsonApiResponse;
use Flarum\Locale\TranslatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Rejects oversized contact requests before Flarum parses the JSON body.
 *
 * The controller checks the decoded payload as well, but this early guard
 * prevents a large request from being buffered and decoded in the first place.
 */
final class EnforceContactRequestSize implements MiddlewareInterface
{
    public function __construct(
        private readonly ContactSettings $settings,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== 'POST' || ! preg_match('#/api/contact$#', $request->getUri()->getPath())) {
            return $handler->handle($request);
        }

        $max = $this->settings->maxRequestBytes();
        $contentLength = $request->getHeaderLine('Content-Length');
        $bodySize = $request->getBody()->getSize();

        $tooLarge = (is_numeric($contentLength) && (int) $contentLength > $max)
            || (is_int($bodySize) && $bodySize > $max);

        if (! $tooLarge) {
            return $handler->handle($request);
        }

        return new JsonApiResponse([
            'errors' => [[
                'status' => '413',
                'code' => 'payload_too_large',
                'detail' => $this->translator->trans('cengizkemec-contact-page.forum.errors.payload_too_large'),
            ]],
        ], 413);
    }
}
