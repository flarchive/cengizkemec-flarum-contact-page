<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Support;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * Strict email validation.
 *
 * A plain FILTER_VALIDATE_EMAIL is not enough for header safety: Symfony's
 * Address parser is authoritative about what the mailer will accept. We also
 * require the parsed address to equal the input so a value such as
 * `Name <victim@example.com>` can never smuggle a display name or header.
 *
 * Flarum installations may use a local sender such as `noreply@localhost`, so
 * domain dots are only required where a routable visitor address is expected.
 */
final class EmailAddress
{
    public static function isValid(string $email, bool $requireDomainDot = false): bool
    {
        $email = trim($email);

        if ($email === '' || strlen($email) > 254) {
            return false;
        }

        if (Text::containsNewline($email) || str_contains($email, "\0")) {
            return false;
        }

        try {
            $address = new Address($email);
        } catch (RfcComplianceException) {
            return false;
        }

        if (strcasecmp($address->getAddress(), $email) !== 0) {
            return false;
        }

        if ($requireDomainDot) {
            $domain = substr(strrchr($address->getAddress(), '@') ?: '', 1);

            if (! str_contains($domain, '.')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string|null The normalized address, or null when invalid.
     */
    public static function normalize(mixed $value, bool $requireDomainDot = false): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $email = trim((string) $value);

        return self::isValid($email, $requireDomainDot) ? $email : null;
    }
}
