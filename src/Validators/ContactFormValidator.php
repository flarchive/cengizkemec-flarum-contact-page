<?php

/*
 * This file is part of the Contact Page extension for Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Cengizkemec\ContactPage\Validators;

use Cengizkemec\ContactPage\Support\ContactSettings;
use Cengizkemec\ContactPage\Support\EmailAddress;
use Cengizkemec\ContactPage\Support\Text;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\User\User;

/**
 * Validates and normalizes every supported contact form field.
 *
 * Only enabled fields are read from the request. This prevents an attacker
 * from injecting data through a field the administrator has switched off.
 */
final class ContactFormValidator
{
    public function __construct(
        private readonly ContactSettings $settings,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validate(array $input, User $actor): array
    {
        $errors = [];
        $data = [];

        $nameField = $this->settings->field('name');
        if ($nameField['enabled']) {
            $name = Text::singleLine($input['name'] ?? '');
            $data['name'] = $name;
            $this->validateRequired($errors, 'name', $nameField, $name);

            if ($name !== '') {
                $length = Text::length($name);
                if ($length < 2) {
                    $errors['name'] = $this->trans('cengizkemec-contact-page.forum.validation.name_too_short');
                } elseif ($length > 100) {
                    $errors['name'] = $this->trans('cengizkemec-contact-page.forum.validation.name_too_long');
                }
            }
        }

        $emailField = $this->settings->field('email');
        if ($emailField['enabled']) {
            $email = Text::singleLine($input['email'] ?? '', false);
            $data['email'] = $email;
            $this->validateRequired($errors, 'email', $emailField, $email);

            if ($email !== '' && ! EmailAddress::isValid($email, true)) {
                $errors['email'] = $this->trans('cengizkemec-contact-page.forum.validation.email_invalid');
            }
        }

        $subjectField = $this->settings->field('subject');
        if ($subjectField['enabled']) {
            $subject = Text::singleLine($input['subject'] ?? '');
            $data['subject'] = $subject;
            $this->validateRequired($errors, 'subject', $subjectField, $subject);

            if ($subject !== '' && Text::length($subject) > $this->settings->subjectMaxLength()) {
                $errors['subject'] = $this->trans('cengizkemec-contact-page.forum.validation.subject_too_long', [
                    'max' => $this->settings->subjectMaxLength(),
                ]);
            }
        }

        $messageField = $this->settings->field('message');
        if ($messageField['enabled']) {
            $message = Text::multiline($input['message'] ?? '');
            $data['message'] = $message;
            $this->validateRequired($errors, 'message', $messageField, $message);

            if ($message !== '') {
                $length = Text::length($message);
                if ($length < $this->settings->messageMinLength()) {
                    $errors['message'] = $this->trans('cengizkemec-contact-page.forum.validation.message_too_short', [
                        'min' => $this->settings->messageMinLength(),
                    ]);
                } elseif ($length > $this->settings->messageMaxLength()) {
                    $errors['message'] = $this->trans('cengizkemec-contact-page.forum.validation.message_too_long', [
                        'max' => $this->settings->messageMaxLength(),
                    ]);
                }
            }
        }

        $categories = $this->settings->categories();
        $categoryField = $this->settings->field('category');
        if ($categoryField['enabled'] && $categories !== []) {
            $category = Text::singleLine($input['category'] ?? '');
            $data['category'] = $category;

            if ($category === '') {
                if ($categoryField['required']) {
                    $errors['category'] = $this->trans('cengizkemec-contact-page.forum.validation.required', [
                        'field' => $this->label('category', $categoryField['label']),
                    ]);
                }
            } elseif (! in_array($category, $categories, true)) {
                $errors['category'] = $this->trans('cengizkemec-contact-page.forum.validation.category_invalid');
            }
        } else {
            $data['category'] = '';
        }

        $phoneField = $this->settings->field('phone');
        if ($phoneField['enabled']) {
            $phone = Text::singleLine($input['phone'] ?? '');
            $data['phone'] = $phone;
            $this->validateRequired($errors, 'phone', $phoneField, $phone);

            if ($phone !== '' && ! preg_match('/^[0-9+().\-\s]{3,30}$/', $phone)) {
                $errors['phone'] = $this->trans('cengizkemec-contact-page.forum.validation.phone_invalid');
            }
        }

        $privacyField = $this->settings->field('privacy');
        $privacy = false;
        if ($privacyField['enabled']) {
            $privacy = $this->accepted($input['privacy'] ?? false);
            $data['privacy'] = $privacy;

            if ($privacyField['required'] && ! $privacy) {
                $errors['privacy'] = $this->trans('cengizkemec-contact-page.forum.validation.privacy_required', [
                    'field' => $this->label('privacy', $privacyField['label']),
                ]);
            }
        }

        $data['actorRegistered'] = $actor->exists;

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $data;
    }

    /**
     * @param array<string, string> $errors
     * @param array{enabled: bool, required: bool, label: string, placeholder: string} $field
     */
    private function validateRequired(array &$errors, string $key, array $field, string $value): void
    {
        if ($field['required'] && $value === '') {
            $errors[$key] = $this->trans('cengizkemec-contact-page.forum.validation.required', [
                'field' => $this->label($key, $field['label']),
            ]);
        }
    }

    private function label(string $field, string $customLabel): string
    {
        if ($customLabel !== '') {
            return $customLabel;
        }

        return $this->trans('cengizkemec-contact-page.forum.fields.'.$field.'_label');
    }

    private function accepted(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (! is_scalar($value)) {
            return false;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function trans(string $key, array $params = []): string
    {
        return $this->translator->trans($key, $params);
    }
}
