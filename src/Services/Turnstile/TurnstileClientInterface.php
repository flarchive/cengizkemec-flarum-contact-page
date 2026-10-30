<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Services\Turnstile;

interface TurnstileClientInterface
{
    public function verify(string $token, string $secret, int $timeoutSeconds): TurnstileResult;
}
