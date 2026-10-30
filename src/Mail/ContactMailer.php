<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Mail;

use Carbon\CarbonImmutable;
use Cengizkemec\ContactPage\Support\ContactException;
use Cengizkemec\ContactPage\Support\ContactSettings;
use Cengizkemec\ContactPage\Support\EmailAddress;
use Cengizkemec\ContactPage\Support\LogSanitizer;
use Cengizkemec\ContactPage\Support\Text;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\DriverInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Builds and sends the administrator notification through Flarum's configured
 * mailer. There is intentionally no separate SMTP configuration here.
 */
final class ContactMailer
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly DriverInterface $driver,
        private readonly ContactSettings $settings,
        private readonly SettingsRepositoryInterface $settingsRepository,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $data Validated data from ContactFormValidator.
     */
    public function send(array $data): void
    {
        if (! $this->driver->canSend()) {
            $this->logger->error('Contact Page cannot send mail because the configured Flarum mail driver is not able to deliver messages.');

            throw $this->mailUnavailable('driver_cannot_send');
        }

        $recipient = EmailAddress::normalize($this->settings->recipientEmail(), true);
        if ($recipient === null) {
            $this->logger->error('Contact Page has no valid recipient email configured.');

            throw $this->mailUnavailable('invalid_recipient');
        }

        $from = EmailAddress::normalize($this->settingsRepository->get('mail_from'));
        if ($from === null) {
            $this->logger->error('Contact Page cannot send mail because Flarum has no valid mail_from address configured.');

            throw $this->mailUnavailable('invalid_mail_from');
        }

        $forumTitle = Text::singleLine($this->settingsRepository->get('forum_title') ?? '', false);
        $senderName = Text::singleLine($this->settings->string('sender_name'), true);
        if ($senderName === '') {
            $senderName = $forumTitle !== '' ? $forumTitle : 'Flarum';
        }

        $prefix = Text::singleLine($this->settings->string('email_subject_prefix'), true);
        $subject = Text::singleLine($data['subject'] ?? '', true);
        if ($subject === '') {
            $subject = $this->translator->trans('cengizkemec-contact-page.email.no_subject');
        }

        $mailSubject = trim($prefix.' '.$subject);
        $mailSubject = mb_substr($mailSubject !== '' ? $mailSubject : $subject, 0, 250, 'UTF-8');

        $replyTo = null;
        if ($this->settings->bool('reply_to_enabled') && ! empty($data['email'])) {
            $replyTo = EmailAddress::normalize($data['email']);
        }

        $previousLocale = $this->translator->getLocale();
        $this->translator->setLocale($this->emailLocale($previousLocale));

        try {
            $viewData = $this->viewData($data, $forumTitle);

            $this->mailer->send(
                [
                    'html' => 'contact-page::emails.html.contact',
                    'text' => 'contact-page::emails.plain.contact',
                ],
                $viewData,
                function (Message $message) use ($recipient, $from, $senderName, $mailSubject, $replyTo): void {
                    $message->to($recipient);
                    $message->subject($mailSubject);
                    $message->from($from, $senderName);

                    if ($replyTo !== null) {
                        $message->replyTo($replyTo);
                    }
                }
            );
        } catch (Throwable $e) {
            $this->logger->error('Contact Page email delivery failed.', [
                'exception_class' => $e::class,
                'reason' => LogSanitizer::redact($e->getMessage()),
            ]);

            throw new ContactException(
                503,
                'mail_delivery_failed',
                'cengizkemec-contact-page.forum.errors.mail_delivery_failed',
                [],
                ['reason' => 'mail_transport_error']
            );
        } finally {
            $this->translator->setLocale($previousLocale);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function viewData(array $data, string $forumTitle): array
    {
        $name = (string) ($data['name'] ?? '');
        $email = (string) ($data['email'] ?? '');
        $subject = (string) ($data['subject'] ?? '');
        $message = (string) ($data['message'] ?? '');
        $category = (string) ($data['category'] ?? '');
        $phone = (string) ($data['phone'] ?? '');

        return [
            'mailTitle' => $this->translator->trans('cengizkemec-contact-page.email.heading'),
            'contactName' => $name !== ''
                ? $name
                : $this->translator->trans('cengizkemec-contact-page.email.unknown_sender'),
            'contactEmail' => $email !== ''
                ? $email
                : $this->translator->trans('cengizkemec-contact-page.email.not_provided'),
            'contactSubject' => $subject !== ''
                ? $subject
                : $this->translator->trans('cengizkemec-contact-page.email.no_subject'),
            'contactMessage' => $message !== ''
                ? $message
                : $this->translator->trans('cengizkemec-contact-page.email.no_message'),
            'contactCategory' => $category !== ''
                ? $category
                : $this->translator->trans('cengizkemec-contact-page.email.no_category'),
            'contactPhone' => $phone,
            'phoneEnabled' => $this->settings->field('phone')['enabled'],
            'privacyEnabled' => $this->settings->field('privacy')['enabled'],
            'privacyAccepted' => ! empty($data['privacy']),
            'submittedAt' => CarbonImmutable::now()->toIso8601String(),
            'forumTitle' => $forumTitle !== '' ? $forumTitle : 'Flarum',
            'submitterType' => ! empty($data['actorRegistered'])
                ? $this->translator->trans('cengizkemec-contact-page.email.registered_user')
                : $this->translator->trans('cengizkemec-contact-page.email.guest'),
        ];
    }

    private function emailLocale(string $current): string
    {
        $current = strtolower($current);

        if (str_starts_with($current, 'tr')) {
            return 'tr';
        }

        if (str_starts_with($current, 'en')) {
            return 'en';
        }

        $default = strtolower($this->settings->string('default_language'));

        return in_array($default, ['tr', 'en'], true) ? $default : 'en';
    }

    private function mailUnavailable(string $reason): ContactException
    {
        return new ContactException(
            503,
            'mail_unavailable',
            'cengizkemec-contact-page.forum.errors.mail_unavailable',
            [],
            ['reason' => $reason]
        );
    }
}
