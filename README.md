<h1 align="center">Laravel Bachs</h1>

<p align="center">
  An independent, community-maintained <a href="https://laravel.com">Laravel</a>
  integration for <a href="https://bachs.io">Bachs.io</a>.
</p>

<p align="center">
  <img src="https://github.com/okeke-dev/laravel-bachs/actions/workflows/ci.yml/badge.svg" alt="CI Status">
  <img src="https://poser.pugx.org/okeke-dev/laravel-bachs/v" alt="Latest Version">
  <img src="https://poser.pugx.org/okeke-dev/laravel-bachs/downloads" alt="Total Downloads">
  <img src="https://poser.pugx.org/okeke-dev/laravel-bachs/license" alt="License">
</p>

---

## Introduction

Laravel Bachs brings [Bachs.io](https://bachs.io) billing to Laravel: invoices,
subscriptions, checkout sessions, payment methods, webhooks, and more — with a
`Cashier`-style API that fits naturally into your application.

> **Status:** this package is in early development (`0.x`). The API is not
> stable yet and only the foundation (connections + configuration) exists so
> far. See [`CHANGELOG.md`](CHANGELOG.md) and the open issues for progress.

## Requirements

- PHP `^8.2`
- Laravel `^12.0|^13.0`

## Installation

```bash
composer require okeke-dev/laravel-bachs
```

The package registers its own service provider automatically. Publish the
configuration file:

```bash
php artisan vendor:publish --tag=bachs-config
```

## Configuration

Set your credentials in `.env`:

```dotenv
BACHS_SECRET_KEY=sk_sandbox_xxxxxxxxxxxxxx
BACHS_ENV=sandbox
```

- `BACHS_ENV` selects the sandbox (`https://sandbox-api.bachs.io/v1`) or live
  (`https://api.bachs.io/v1`) base URL.
- `BACHS_BASE_URL` overrides the base URL entirely when set.

The published `config/bachs.php` also supports multiple named connections for
applications that talk to several Bachs accounts:

```php
// config/bachs.php
'connections' => [
    'default' => [/* ... */],
    'partner' => [/* ... */],
],
```

## Usage

```php
use OkekeDev\Bachs\Facades\Bachs;

$client = Bachs::connection();          // default connection
$client = Bachs::connection('partner'); // named connection
```

### Low-level transport

`BachsClient` is the HTTP transport used by the resource layer. It handles
authentication, JSON, timeouts, idempotency keys, safe retries, and typed
exceptions:

```php
$response = $client->get('products', ['limit' => 20]);
$response->status();      // 200
$response->json();        // decoded payload
$response->requestId();   // x-request-id, for support

$client->post('customers', ['email' => 'a@b.com'], 'idem_123');
```

- **Retries:** safe methods (`GET`/`HEAD`/`OPTIONS`) and requests carrying an
  `Idempotency-Key` retry on 429/5xx and network failures. Mutations without
  an idempotency key are never blind-retried.
- **Exceptions:** non-2xx responses throw typed exceptions —
  `BachsAuthenticationException` (401), `BachsValidationException` (422),
  `BachsNotFoundException` (404), `BachsRateLimitException` (429),
  `BachsConflictException` (409), `BachsNetworkException`, and the base
  `BachsApiException`.

> Higher-level billing resources (products, customers, payments,
> subscriptions) are not exposed yet — they arrive in later milestones.

## Testing

```bash
composer check        # tests + style + static analysis
composer test         # Pest
composer test:style   # Pint
composer test:types   # PHPStan (level 6)
```

## Changelog

Please see [`CHANGELOG.md`](CHANGELOG.md) for what changed recently.

## Contributing

Please see [`CONTRIBUTING.md`](CONTRIBUTING.md) for details.

## Security

Please see [`SECURITY.md`](SECURITY.md) for reporting vulnerabilities.

## Credits

- [Okeke Chimezie Glory](https://github.com/okekechimezieglory)
- [Bachs.io](https://bachs.io) — the API provider this package wraps.
- [Laravel Cashier](https://laravel.com/docs/billing) — whose API design this
  package is inspired by.

## License

The MIT License (MIT). Please see [`LICENSE.md`](LICENSE.md) for more
information.
