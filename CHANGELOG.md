# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and the project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

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
