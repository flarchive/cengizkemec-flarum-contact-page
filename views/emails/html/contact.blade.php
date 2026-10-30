<x-mail::html :greeting="false" :signoff="false">
    <x-slot:header>
        <h2>{{ $mailTitle }}</h2>
    </x-slot:header>

    <x-slot:content>
        <p>{{ $translator->trans('cengizkemec-contact-page.email.intro') }}</p>

        <table role="presentation" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <th style="text-align: left; padding: 6px 12px 6px 0; vertical-align: top; width: 160px;">{{ $translator->trans('cengizkemec-contact-page.email.name_label') }}</th>
                <td style="padding: 6px 0; vertical-align: top;">{{ $contactName }}</td>
            </tr>
            <tr>
                <th style="text-align: left; padding: 6px 12px 6px 0; vertical-align: top;">{{ $translator->trans('cengizkemec-contact-page.email.email_label') }}</th>
                <td style="padding: 6px 0; vertical-align: top;">{{ $contactEmail }}</td>
            </tr>
            <tr>
                <th style="text-align: left; padding: 6px 12px 6px 0; vertical-align: top;">{{ $translator->trans('cengizkemec-contact-page.email.subject_label') }}</th>
                <td style="padding: 6px 0; vertical-align: top;">{{ $contactSubject }}</td>
            </tr>
            <tr>
                <th style="text-align: left; padding: 6px 12px 6px 0; vertical-align: top;">{{ $translator->trans('cengizkemec-contact-page.email.category_label') }}</th>
                <td style="padding: 6px 0; vertical-align: top;">{{ $contactCategory }}</td>
            </tr>
            @if ($phoneEnabled && $contactPhone !== '')
                <tr>
                    <th style="text-align: left; padding: 6px 12px 6px 0; vertical-align: top;">{{ $translator->trans('cengizkemec-contact-page.email.phone_label') }}</th>
                    <td style="padding: 6px 0; vertical-align: top;">{{ $contactPhone }}</td>
                </tr>
            @endif
            <tr>
                <th style="text-align: left; padding: 6px 12px 6px 0; vertical-align: top;">{{ $translator->trans('cengizkemec-contact-page.email.submitted_at_label') }}</th>
                <td style="padding: 6px 0; vertical-align: top;">{{ $submittedAt }}</td>
            </tr>
            <tr>
                <th style="text-align: left; padding: 6px 12px 6px 0; vertical-align: top;">{{ $translator->trans('cengizkemec-contact-page.email.submitter_type_label') }}</th>
                <td style="padding: 6px 0; vertical-align: top;">{{ $submitterType }}</td>
            </tr>
            @if ($privacyEnabled)
                <tr>
                    <th style="text-align: left; padding: 6px 12px 6px 0; vertical-align: top;">{{ $translator->trans('cengizkemec-contact-page.email.privacy_label') }}</th>
                    <td style="padding: 6px 0; vertical-align: top;">{{ $privacyAccepted ? $translator->trans('cengizkemec-contact-page.email.yes') : $translator->trans('cengizkemec-contact-page.email.no') }}</td>
                </tr>
            @endif
        </table>

        <h3>{{ $translator->trans('cengizkemec-contact-page.email.message_label') }}</h3>
        <div style="white-space: pre-wrap;">{{ $contactMessage }}</div>
    </x-slot:content>

    <x-slot:footer>
        <p>{{ $translator->trans('cengizkemec-contact-page.email.footer', ['forumTitle' => $forumTitle]) }}</p>
    </x-slot:footer>
</x-mail::html>
