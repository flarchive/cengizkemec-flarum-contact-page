# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and the project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-11-02

### Added

- Added an Appearance settings section with a custom CSS field. The CSS is
  injected only while the contact page is open and is limited to 50,000
  characters.
- Added a required-fields hint above the form when at least one visible field is
  required.

### Changed

- Moved the privacy policy link below the privacy acceptance label so it no
  longer interrupts the checkbox text.

## [1.0.2] - 2026-10-30

### Fixed

- Fixed the contact form failing to render when Turnstile was enabled, which
  prevented the security widget (and the whole form) from appearing.

## [1.0.1] - 2026-10-30

### Fixed

- Kept a valid Turnstile token when the server returns field validation errors,
  and reset it after failures that may have consumed it.
- Cleared stale form errors between submission attempts.
- Used Unicode character counts in the client-side name, subject and message
  length checks so they match the server.

## [1.0.0] - 2026-10-30

### Added

- Dedicated `/contact` page with native Flarum layout and responsive styling.
- Configurable sidebar navigation entry.
- Configurable contact form fields, labels, placeholders and categories.
- Four access modes with server-side enforcement and optional email verification.
- Flarum mail transport integration with HTML and plain-text templates.
- Optional Cloudflare Turnstile verification with hostname, action, freshness
  and replay checks.
- Honeypot, time trap, cooldown, per-IP rate limiting, duplicate detection and
  request-size limits.
- HMAC-based privacy-conscious storage for rate-limit and duplicate keys.
- Server-side settings validation.
- English and Turkish localization.
- Unit and integration tests, PHPStan configuration and GitHub Actions workflows.
