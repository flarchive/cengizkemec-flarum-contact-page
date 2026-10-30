<x-mail::plain :greeting="false" :signoff="false">
    <x-slot:header>
{{ $mailTitle }}
    </x-slot:header>

    <x-slot:content>
{{ $translator->trans('cengizkemec-contact-page.email.intro') }}

{{ $translator->trans('cengizkemec-contact-page.email.name_label') }}: {{ $contactName }}
{{ $translator->trans('cengizkemec-contact-page.email.email_label') }}: {{ $contactEmail }}
{{ $translator->trans('cengizkemec-contact-page.email.subject_label') }}: {{ $contactSubject }}
{{ $translator->trans('cengizkemec-contact-page.email.category_label') }}: {{ $contactCategory }}
@if ($phoneEnabled && $contactPhone !== '')
{{ $translator->trans('cengizkemec-contact-page.email.phone_label') }}: {{ $contactPhone }}
@endif
{{ $translator->trans('cengizkemec-contact-page.email.submitted_at_label') }}: {{ $submittedAt }}
{{ $translator->trans('cengizkemec-contact-page.email.submitter_type_label') }}: {{ $submitterType }}
@if ($privacyEnabled)
{{ $translator->trans('cengizkemec-contact-page.email.privacy_label') }}: {{ $privacyAccepted ? $translator->trans('cengizkemec-contact-page.email.yes') : $translator->trans('cengizkemec-contact-page.email.no') }}
@endif

{{ $translator->trans('cengizkemec-contact-page.email.message_label') }}:
{{ $contactMessage }}
    </x-slot:content>

    <x-slot:footer>
{{ $translator->trans('cengizkemec-contact-page.email.footer', ['forumTitle' => $forumTitle]) }}
    </x-slot:footer>
</x-mail::plain>
