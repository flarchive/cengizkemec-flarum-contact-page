<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Api\Controller;

use Cengizkemec\ContactPage\Mail\ContactMailer;
use Cengizkemec\ContactPage\Services\AccessPolicy;
use Cengizkemec\ContactPage\Services\BotDetector;
use Cengizkemec\ContactPage\Services\SubmissionGuard;
use Cengizkemec\ContactPage\Services\Turnstile\TurnstileVerifier;
use Cengizkemec\ContactPage\Support\ContactException;
use Cengizkemec\ContactPage\Support\ContactSettings;
use Cengizkemec\ContactPage\Support\PrivacyHasher;
use Cengizkemec\ContactPage\Validators\ContactFormValidator;
use Flarum\Api\JsonApiResponse;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles POST /api/contact.
 *
 * Every access decision, validation rule and spam check is enforced here, not
 * in the browser. The frontend only mirrors these checks for a better UX.
 */
final class SubmitContactController implements RequestHandlerInterface
{
    public function __construct(
        private readonly ContactSettings $settings,
        private readonly AccessPolicy $accessPolicy,
        private readonly ContactFormValidator $validator,
        private readonly BotDetector $botDetector,
        private readonly SubmissionGuard $guard,
        private readonly TurnstileVerifier $turnstile,
        private readonly ContactMailer $mailer,
        private readonly PrivacyHasher $hasher,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        try {
            $denialKey = $this->accessPolicy->denialKey($actor);
            if ($denialKey !== null) {
                throw new ContactException(
                    403,
                    'permission_denied',
                    $denialKey,
                    [],
                    ['reason' => 'access_denied']
                );
            }

            $maxBytes = $this->settings->maxRequestBytes();
            $contentLength = $request->getHeaderLine('Content-Length');

            if (is_numeric($contentLength) && (int) $contentLength > $maxBytes) {
                throw new ContactException(
                    413,
                    'payload_too_large',
                    'cengizkemec-contact-page.forum.errors.payload_too_large',
                    [],
                    ['reason' => 'content_length']
                );
            }

            $body = $request->getParsedBody();

            if (! is_array($body)) {
                throw new ContactException(
                    400,
                    'bad_request',
                    'cengizkemec-contact-page.forum.errors.bad_request',
                    [],
                    ['reason' => 'invalid_body']
                );
            }

            $encoded = json_encode($body);
            if ($encoded === false || strlen($encoded) > $maxBytes) {
                throw new ContactException(
                    413,
                    'payload_too_large',
                    'cengizkemec-contact-page.forum.errors.payload_too_large',
                    [],
                    ['reason' => 'encoded_body']
                );
            }

            $botReason = $this->botDetector->reason($body);
            if ($botReason !== null) {
                // Do not tell a bot that it was detected. No email is sent.
                $this->logger->info('Contact Page bot signal detected.', [
                    'reason' => $botReason,
                ]);

                return $this->success();
            }

            $data = $this->validator->validate($body, $actor);

            $token = $body['turnstileToken'] ?? '';
            $this->turnstile->verify(
                is_scalar($token) ? (string) $token : '',
                $request->getUri()->getHost()
            );

            $ip = $request->getAttribute('ipAddress');
            $ipHash = $this->hasher->hash(is_string($ip) && $ip !== '' ? $ip : '0.0.0.0');

            $this->guard->assertRateLimit($actor, $ipHash);
            $this->guard->assertNotDuplicate($ipHash, $data);
            $this->guard->record($ipHash);

            try {
                $this->mailer->send($data);
            } catch (ContactException $e) {
                // Allow the visitor to retry once the mail problem is fixed.
                $this->guard->release($ipHash, $data);
                throw $e;
            }

            return $this->success();
        } catch (ContactException $e) {
            return $this->error($e);
        }
    }

    private function success(): JsonApiResponse
    {
        return new JsonApiResponse([
            'meta' => [
                'message' => $this->translator->trans('cengizkemec-contact-page.forum.success_message'),
            ],
        ], 201);
    }

    private function error(ContactException $e): JsonApiResponse
    {
        $headers = [];

        if ($e->status === 429 && isset($e->params['seconds'])) {
            $headers['Retry-After'] = (string) max(1, (int) $e->params['seconds']);
        }

        if ($e->status >= 500 || $e->status === 429) {
            $this->logger->warning('Contact Page submission failed.', [
                'status' => $e->status,
                'reason' => $e->logContext['reason'] ?? 'unknown',
            ]);
        }

        return new JsonApiResponse([
            'errors' => [[
                'status' => (string) $e->status,
                'code' => $e->errorCode,
                'detail' => $this->translator->trans($e->messageKey, $e->params),
            ]],
        ], $e->status, $headers);
    }
}
