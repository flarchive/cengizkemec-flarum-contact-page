# Configuration reference

All settings are stored under the `cengizkemec-contact-page.` namespace and can
be changed from **Administration → Extensions → Contact Page**. The values are
validated again on the server by the extension; client-side form checks are only
a convenience.

## General

| Setting | Default | Purpose |
| --- | --- | --- |
| Enable contact page | Off | Makes the page and form available. Existing settings are retained when disabled. |
| Show in sidebar navigation | On | Adds the contact link to the index sidebar when the visitor is allowed to use the form. |
| Navigation label | Empty | Overrides the translated **Contact / İletişim** label. |
| Page title | Empty | Overrides the translated page title and the HTML document title. |
| Page description | Empty | Plain-text description shown below the title and used as the meta description. |
| Success message | Empty | Shown after a successful submission. |
| Contact categories | Empty | One category per line. The category field is hidden when the list is empty. |

## Access permissions

| Setting | Default | Purpose |
| --- | --- | --- |
| Access mode | Everyone | Everyone, registered users only, guests only, or disabled. |
| Allow guest submissions | On | Narrows access for unauthenticated visitors. |
| Allow registered user submissions | On | Narrows access for logged-in users. |
| Require verified email | Off | Denies logged-in users whose email is not confirmed. Guests are unaffected. |
| Prefill name and email | On | Prefills the fields from the current account. The user can edit them. |

Effective rules:

| Access mode | Guest | Registered |
| --- | --- | --- |
| Everyone | Allowed if guest submissions are allowed | Allowed if registered submissions are allowed and verification passes |
| Registered users only | Denied | Allowed if registered submissions are allowed and verification passes |
| Guests only | Allowed if guest submissions are allowed | Denied |
| Disabled | Denied | Denied |

The server applies these rules to every API request. Hiding the nav item in the
browser does not grant or remove access.

## Form fields

Each field has its own enable, required, label and — except for the privacy
acceptance — placeholder settings. Leaving a label or placeholder empty uses the
translated default.

Supported fields: full name, email, subject, message, category, phone and privacy
acceptance.

Defaults:

| Field | Enabled | Required |
| --- | --- | --- |
| Full name | Yes | Yes |
| Email | Yes | Yes |
| Subject | Yes | Yes |
| Message | Yes | Yes |
| Category | No | No |
| Phone | No | No |
| Privacy acceptance | Yes | Yes |

The category field additionally requires at least one configured category.

## Message limits

| Setting | Default | Range |
| --- | --- | --- |
| Minimum message length | 10 | 1–50,000 characters |
| Maximum message length | 5,000 | 1–50,000 characters |
| Maximum subject length | 150 | 1–500 characters |
| Privacy policy URL | Empty | Optional HTTP/HTTPS URL |

The minimum may not exceed the maximum. Message length is measured in Unicode
characters after normalization.

## Email

| Setting | Default | Purpose |
| --- | --- | --- |
| Recipient email address | Empty | Required when the page is enabled. Must be a valid routable address. |
| Email subject prefix | Empty | Prepended to the visitor's subject. |
| Sender name | Empty | Friendly sender name. The address always comes from Flarum's `mail_from`. |
| Use visitor email as Reply-To | On | Applies only to validated visitor addresses. |

Flarum's global mail settings remain authoritative:

- **Mail driver**, host, credentials and encryption are configured in Flarum's
  own mail settings.
- **`mail_from`** is the sender address.
- **`mail_format`** selects multipart, HTML-only or plain-text delivery.

If the configured driver cannot send, or delivery throws, the extension returns a
failure and does not show a success state.

## Security and Turnstile

| Setting | Default | Purpose |
| --- | --- | --- |
| Enable Turnstile | Off | Enables server-side Cloudflare verification. |
| Site key | Empty | Public Turnstile key. |
| Secret key | Empty | Private key. Never sent to the frontend or logged. |
| Theme | Auto | Auto, light or dark. |
| Appearance | Always | Always visible or interaction-only. |
| HTTP timeout | 5 s | 1–30 seconds for Siteverify. |
| Expected hostname | Empty | Optional override for proxied installations. |

When Turnstile is enabled, both keys are required. The server checks:

- Siteverify returned `success: true`.
- The response hostname matches the configured or request hostname.
- The response action is exactly `contact`.
- The challenge timestamp is recent.
- The token has not already been used (hashed replay cache).

Any failure blocks the email. A Siteverify timeout or transport error fails
closed.

## Rate limiting and abuse protection

| Setting | Default | Purpose |
| --- | --- | --- |
| Enable rate limiting | On | Enables the cooldown and per-IP window. |
| Maximum submissions per window | 5 | Range 1–1,000. |
| Rate limit window | 600 s | Range 10–86,400 seconds. |
| Submission cooldown | 30 s | Minimum time between submissions. 0 disables it. |
| Honeypot | On | Hidden field; filled fields receive a fake success with no email. |
| Time trap | On | Forms submitted unrealistically fast receive a fake success. |
| Minimum form fill time | 2 s | Range 0–60 seconds. |
| Duplicate window | 300 s | Identical submissions are rejected. 0 disables it. |
| Maximum request size | 65,536 bytes | Early request-size guard, range 1 KB–1 MB. |

Rate-limit keys use HMAC-SHA-256 hashes of the visitor IP with a per-installation
salt. Raw IP addresses are not stored. Cache entries expire automatically.

The extension also uses:

- Flarum's normal CSRF middleware for the API route.
- Strict server-side field validation and normalization.
- A per-IP submission limit and cooldown.
- Duplicate detection.
- Honeypot and time-trap signals.
- Request-size checks before JSON parsing.
- Optional Turnstile.

## Localization

| Setting | Default | Purpose |
| --- | --- | --- |
| Default language | Use current forum language | Fallback language for email labels when the current language is neither English nor Turkish. |

All extension UI labels, validation messages, admin labels and email labels are
translated through Flarum's locale system. The current Flarum language is used
when it is English or Turkish; otherwise the configured fallback or English is
used for email content.

## Permission

The extension registers one optional permission:

**Bypass contact form rate limits** — members of groups with this permission are
not subject to the cooldown or per-IP window. Other protections, including
Turnstile, validation, duplicate detection and request-size limits, still apply.
