# Security Policy

## Supported versions

Security fixes are provided for the latest 1.x release on a best-effort basis.

## Reporting a vulnerability

Please **do not** open a public issue for a security vulnerability. Use GitHub's
private vulnerability reporting on the repository's **Security** tab, or contact
the maintainer through the address on the GitHub profile.

Include:

- Affected extension version, Flarum version and PHP version.
- A description of the issue and its impact.
- Reproduction steps or a proof of concept.
- Suggested remediation, if you have one.

You should receive an acknowledgement as soon as reasonably possible. Please do
not publish details before a fix has been released.

## Scope

The extension covers the contact page, API endpoint, validation, access control,
mail composition, Turnstile verification, rate limiting and settings validation.
Flarum core, the web server, the mail server, Cloudflare and the database are
outside the extension's control.

See [docs/security.md](docs/security.md) for the full threat model and controls.
