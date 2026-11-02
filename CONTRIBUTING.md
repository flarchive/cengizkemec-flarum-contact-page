# Contributing

Thank you for considering a contribution to Contact Page for Flarum 2.0.

## Before you start

- Search existing issues and pull requests first.
- For security vulnerabilities, follow [SECURITY.md](SECURITY.md) instead of
  opening a public issue.
- For large features or breaking changes, open an issue to discuss the design
  before writing code.

## Development setup

Requirements: PHP 8.3+, Composer, Node.js 20+ and npm.

```bash
composer install
composer test:setup
composer test
composer analyse
```

Frontend:

```bash
cd js
npm install
npm run check-typings
npm run build
```

## Coding standards

- Follow the surrounding Flarum 2.0 conventions and PSR-12 style.
- Keep PHP code typed and compatible with PHP 8.3.
- Use Flarum's translation system; do not hardcode user-facing strings.
- Keep security decisions on the server. Frontend checks are for UX only.
- Add or update tests for behaviour changes.
- Keep `js/dist` in sync by running `npm run build` before committing frontend
  changes.
- Do not add a second mail transport, analytics, tracking pixels or unnecessary
  dependencies.
- Do not log visitor message content, email addresses, raw IPs or secrets.

## Translations

Update both `locale/en.yml` and `locale/tr.yml`. The localization parity test
fails if the key sets differ or a value is empty.

When adding a new user-facing string:

1. Add a key to both locale files.
2. Reference it through `app.translator` in JavaScript or the translator in PHP.
3. Run `composer test` and `npm run check-typings`.

## Tests

```bash
composer test              # backend unit and integration tests
composer analyse           # PHPStan level 8
composer validate --strict # Composer metadata
cd js && npm run check-typings && npm run build
```

Integration tests use SQLite by default and create a temporary Flarum
installation under `vendor/flarum/testing/src/integration/tmp`.

## Pull requests

- Keep commits focused and messages meaningful.
- Describe the problem, the solution and any security implications.
- Include test evidence (commands and results).
- Update documentation and the changelog when appropriate.
- Do not include secrets, `.env` files, production credentials or private forum
  data.

## License

By contributing, you agree that your contribution is licensed under the MIT
License.
