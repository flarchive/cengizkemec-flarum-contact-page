# Security

This document describes the security model of Contact Page for Flarum 2.0 and
the limitations administrators should understand.

## Supported versions

Security fixes are provided for the latest 1.x release on a best-effort basis.
Please include the extension version, Flarum version and PHP version in every
report.

## Reporting a vulnerability

Do **not** open a public issue for a security problem. Use GitHub's private
vulnerability reporting on the repository's **Security** tab, or contact the
maintainer through the address listed on the GitHub profile.

Please include:

- A description of the issue and its impact.
- Reproduction steps or a proof of concept.
- The affected extension, Flarum and PHP versions.
- Any suggested remediation.

You should receive an acknowledgement as soon as reasonably possible. Please do
not publish details before a fix has been released.

## Threat model

The extension assumes:

- Flarum's session, CSRF, authentication and authorization layers are trusted.
- The administrator configures a working mail transport and a trusted recipient.
- The forum is served over HTTPS.
- Cloudflare Turnstile is optional; when enabled, Cloudflare is trusted to return
  a verification result, but the extension still validates the response metadata.
- The cache backend used by Flarum is not attacker-controlled.

The extension does not protect against a compromised administrator account, a
compromised server, a malicious mail server, or a compromised Flarum core.

## Server-side enforcement

Every submission is processed by a controller that:

1. Resolves the actor from Flarum's authenticated request.
2. Applies the configured access mode and guest/member switches.
3. Enforces the verified-email requirement, when enabled.
4. Rejects oversized requests before JSON parsing.
5. Detects honeypot and time-trap signals.
6. Validates every enabled field on the server.
7. Verifies Turnstile, if enabled.
8. Applies cooldown, per-IP rate limiting and duplicate detection.
9. Sends mail through Flarum's configured transport.
10. Reports failure instead of success when delivery fails.

The frontend performs some of the same checks for user experience. None of them
are relied on for security.

## Access control

Access modes and the optional guest/member switches are evaluated from settings
on the server. The actor's `exists` and `is_email_confirmed` state is read from
Flarum's user model. Unknown access modes fail closed.

The optional `cengizkemec-contact-page.bypassRateLimit` permission only bypasses
the cooldown and per-IP window. It does not bypass Turnstile, validation,
duplicate detection, request-size limits or access control.

## CSRF

The API route is a normal Flarum API route and remains subject to Flarum's
`CheckCsrfToken` middleware. The extension does not exempt it. Requests without a
valid session token are rejected.

## Input handling

- Strings are normalized (Unicode NFC where available), trimmed and stripped of
  NUL bytes and control characters.
- Single-line fields (name, subject, phone) are collapsed to one line.
- Messages preserve intentional line breaks.
- Email addresses are parsed with Symfony Mime's `Address`. The parsed address
  must equal the submitted value, which rejects display-name smuggling and
  header injection.
- CR/LF is rejected in every value that could become a mail header.
- Subjects and sender names are stripped of tags and newlines before being used
  in headers.
- The recipient and sender addresses come from administrator settings, never from
  the visitor.

## XSS

User content is never rendered as raw HTML:

- The forum page renders values through Mithril text nodes.
- Email templates escape user values with Blade's `{{ }}` syntax.
- Plain-text email bodies are not HTML.

Administrator-provided page titles, descriptions, labels and placeholders are
also rendered as text. HTML is not accepted as page content.

## SQL injection

The extension does not accept SQL from visitors and does not add database tables.
Settings are read through Flarum's settings repository. The only writes are
Flarum's own settings writes, performed by the core API after administrator
authorization.

## Email header injection

- Recipient, sender and Reply-To addresses are validated with Symfony Mime.
- Subject prefix and visitor subject are normalized to a single line.
- Sender display names are normalized to a single line and length-limited.
- No visitor-controlled header is ever added.
- Reply-To is set only from a validated visitor email and only when the option is
  enabled.

## Turnstile

When Turnstile is enabled:

- The secret key is stored in Flarum settings and is never serialized to the
  public forum payload.
- The token is validated against `https://challenges.cloudflare.com/turnstile/v0/siteverify`
  over HTTPS with a configurable timeout.
- `success`, hostname, action (`contact`) and challenge timestamp are checked.
- Successful tokens are hashed and cached for a short replay window.
- Timeouts, transport failures and malformed responses fail closed.
- Tokens and secret keys are never logged.
- The visitor IP is not sent to Cloudflare.

Turnstile is optional. It should be enabled for forms that accept public guest
submissions.

## Rate limiting and privacy

- IP addresses are HMAC-SHA-256 hashed with a random per-installation salt before
  being used as cache keys.
- Duplicate fingerprints are also HMAC-hashed.
- Turnstile replay entries are hashed.
- Cache entries expire automatically according to the configured cooldown,
  window and duplicate TTLs.
- Raw IP addresses are not written to the cache or to logs.
- No analytics, tracking pixels or third-party scripts are added. The only
  optional third-party request is the Cloudflare Turnstile script and Siteverify
  call, and only when the administrator enables Turnstile.

Rate limiting is not a substitute for Turnstile. A distributed attack from many
IP addresses can still reach the form, and a shared IP (NAT, corporate proxy) can
cause legitimate users to share a limit. The permission above can be used for
trusted groups.

## Request size and resource use

- A middleware checks the contact API request before Flarum parses the JSON body.
- The controller checks both `Content-Length` and the encoded body size.
- Field lengths and the maximum message size are enforced by the validator.
- Rate limiting reduces repeated mail transport work.

## Logging

- No visitor name, email address, message content or raw IP is passed to the
  logger.
- Mail transport exceptions are logged with the exception class and a redacted
  message. Common credential/email patterns are removed by a redaction helper.
- Turnstile failures log only reason codes and error codes, never tokens.
- The administrator should still review log retention and access controls on the
  server.

## Known limitations

- The extension relies on `REMOTE_ADDR` as exposed by Flarum. If the forum is
  behind a reverse proxy, rate limiting may see the proxy address unless the
  server/proxy is configured correctly by the administrator.
- The file cache backend is local to one server. Multi-server installations that
  do not share a cache may apply rate limits per server rather than globally.
- The honeypot and time trap are deterrents, not proof of humanity.
- A working mail transport is required. The extension returns a failure when the
  configured driver cannot deliver; it does not queue messages for later delivery.
- The extension cannot prevent a determined attacker from submitting valid,
  low-volume messages manually.
