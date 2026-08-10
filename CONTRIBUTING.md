# Contributing

Thank you for contributing to **Laravel Bachs**!

## Development setup

Requires PHP `^8.2` and Composer 2.

```bash
composer install
```

## Code style

We use [Laravel Pint](https://laravel.com/docs/pint) with the `laravel` preset.

```bash
# fix style
composer format

# check style without modifying
composer test:style
```

## Static analysis

We use [PHPStan](https://phpstan.org/) (via Larastan) at **level 6**.

```bash
composer test:types
```

## Tests

We use [Pest](https://pestphp.com/).

```bash
composer test
```

Run everything before opening a PR:

```bash
composer check
```

## Pull request checklist

- [ ] Code follows the `laravel` Pint preset.
- [ ] PHPStan level 6 passes without errors.
- [ ] New behaviour is covered by Pest tests.
- [ ] `CHANGELOG.md` updated under **Unreleased** (if user-facing).
- [ ] Documentation in `docs/` and `README.md` updated (if user-facing).

## Reporting a security issue

Please read [`SECURITY.md`](SECURITY.md) and do **not** open a public issue.
