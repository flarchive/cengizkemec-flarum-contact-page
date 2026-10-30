<?php

namespace Cengizkemec\ContactPage\Tests\Support;

use Cengizkemec\ContactPage\Services\Turnstile\TurnstileClientInterface;
use Cengizkemec\ContactPage\Services\Turnstile\TurnstileResult;

final class FakeTurnstileClient implements TurnstileClientInterface
{
    public static TurnstileResult $result;
    public static int $calls = 0;

    public static function reset(): void
    {
        self::$result = TurnstileResult::success('localhost', 'contact', gmdate('c'));
        self::$calls = 0;
    }

    public function verify(string $token, string $secret, int $timeoutSeconds): TurnstileResult
    {
        self::$calls++;

        return self::$result;
    }
}
