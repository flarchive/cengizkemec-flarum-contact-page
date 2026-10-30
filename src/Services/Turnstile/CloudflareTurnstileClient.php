<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Services\Turnstile;

use Symfony\Component\HttpClient\HttpClient;
use Throwable;

/**
 * Talks to Cloudflare's official Siteverify endpoint.
 *
 * The visitor IP is deliberately not sent. Cloudflare tokens are single-use,
 * short-lived and bound to a hostname/action, which is sufficient for this
 * form and keeps the amount of data shared with Cloudflare to a minimum.
 */
final class CloudflareTurnstileClient implements TurnstileClientInterface
{
    private const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(string $token, string $secret, int $timeoutSeconds): TurnstileResult
    {
        try {
            $client = HttpClient::create([
                'timeout' => max(1, $timeoutSeconds),
                'max_duration' => max(2, $timeoutSeconds + 2),
            ]);

            $response = $client->request('POST', self::SITEVERIFY_URL, [
                'headers' => ['Accept' => 'application/json'],
                'body' => [
                    'secret' => $secret,
                    'response' => $token,
                ],
            ]);

            $status = $response->getStatusCode();

            if ($status < 200 || $status >= 300) {
                return TurnstileResult::transportError(['http_status' => $status]);
            }

            $data = $response->toArray(false);
        } catch (Throwable $e) {
            return TurnstileResult::transportError([
                'exception_class' => $e::class,
            ]);
        }

        $errorCodes = array_values(array_filter(
            array_map('strval', (array) ($data['error-codes'] ?? [])),
            fn (string $code) => $code !== ''
        ));

        $hostname = isset($data['hostname']) && is_string($data['hostname']) ? $data['hostname'] : null;
        $action = isset($data['action']) && is_string($data['action']) ? $data['action'] : null;
        $challengeTs = isset($data['challenge_ts']) && is_string($data['challenge_ts']) ? $data['challenge_ts'] : null;

        if (($data['success'] ?? false) !== true) {
            return TurnstileResult::failure($errorCodes, $hostname, $action, $challengeTs);
        }

        return TurnstileResult::success($hostname, $action, $challengeTs);
    }
}
