<?php

namespace Cengizkemec\ContactPage\Tests\Support;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

final class CapturingTransport extends AbstractTransport
{
    /**
     * @var Email[]
     */
    public static array $messages = [];

    public static function reset(): void
    {
        self::$messages = [];
    }

    protected function doSend(SentMessage $message): void
    {
        if (CapturingMailDriver::$fail) {
            throw new TransportException('Simulated mail transport failure.');
        }

        $original = $message->getOriginalMessage();

        if (! $original instanceof Email) {
            throw new TransportException('Expected an Email message.');
        }

        self::$messages[] = $original;
    }

    public function __toString(): string
    {
        return 'capturing://';
    }
}
