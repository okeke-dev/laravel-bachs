<h1 align="center">Laravel Bachs</h1>

<p align="center">
  An independent, community-maintained <a href="https://laravel.com">Laravel</a>
  integration for <a href="https://bachs.io">Bachs.io</a>.
</p>

<p align="center">
  <a href="https://github.com/okeke-dev/laravel-bachs/actions/workflows/ci.yml"><img src="https://github.com/okeke-dev/laravel-bachs/actions/workflows/ci.yml/badge.svg" alt="CI Status"></a>
  <a href="https://poser.pugx.org/okeke-dev/laravel-bachs/v"><img src="https://poser.pugx.org/okeke-dev/laravel-bachs/v" alt="Latest Version"></a>
  <a href="https://poser.pugx.org/okeke-dev/laravel-bachs/downloads"><img src="https://poser.pugx.org/okeke-dev/laravel-bachs/downloads" alt="Total Downloads"></a>
  <a href="https://poser.pugx.org/okeke-dev/laravel-bachs/license"><img src="https://poser.pugx.org/okeke-dev/laravel-bachs/license" alt="License"></a>
</p>

---

## Introduction

Laravel Bachs brings [Bachs.io](https://bachs.io) billing to Laravel: products,
customers, subscriptions, checkout sessions, payments, refunds, payment methods,
webhooks, and more — with a `Cashier`-style API that fits naturally into your
application.

> **Requires PHP ^8.2 and Laravel ^12.0|^13.0.** See
> [`CHANGELOG.md`](CHANGELOG.md) for the latest changes.

## Features

- **Products & product groups** — create, list, update, archive/unarchive.
- **Customers** — create, list, update, and portal sessions.
- **Billable trait** — attach billing to any Eloquent model with
  `createAsBachsCustomer()`, `checkout()`, `subscribeTo()`, `subscribed()`,
  `cancel()`, `resume()`, and `billingPortalUrl()`.
- **Checkout sessions** — hosted redirects and inline overlay modals.
- **Subscriptions** — list, get, update, cancel with status helpers.
- **Payments & refunds** — list, get, refund with status helpers.
- **Payment methods** — list and payment rail lookup.
- **Currencies & balances** — supported currencies and balance queries.
- **Media uploads** — multipart upload support with validation.
- **Webhooks** — signature verification, typed events, queue-safe processing,
  idempotency, optional persistence, and automatic local model sync.
- **Blade components** — `<x-bachs::checkout>`, `<x-bachs::checkout-overlay>`,
  and `<x-bachs::subscribe>`.
- **Artisan commands** — `bachs:install`, `bachs:health`, and webhook management.
- **Local models** — opt-in database mirrors for customers, products, payments,
  and subscriptions synced via webhooks.
- **Multi-connection** — talk to multiple Bachs accounts from one application.
- **Typed exceptions** — every error maps to a specific PHP exception class.

## Requirements

- PHP `^8.2`
- Laravel `^12.0|^13.0`

## Installation

```bash
composer require okeke-dev/laravel-bachs
```

The package registers its own service provider automatically. Run the install
command to publish config, migrations, and views:

```bash
php artisan bachs:install
```

Or publish individually:

```bash
php artisan vendor:publish --tag=bachs-config
php artisan vendor:publish --tag=bachs-migrations
php artisan vendor:publish --tag=bachs-views
```

## Configuration

Set your credentials in `.env`:

```dotenv
BACHS_SECRET_KEY=sk_sandbox_xxxxxxxxxxxxxx
BACHS_ENV=sandbox
BACHS_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxx
```

- `BACHS_ENV` selects the sandbox (`https://sandbox-api.bachs.io/v1`) or live
  (`https://api.bachs.io/v1`) base URL.
- `BACHS_BASE_URL` overrides the base URL entirely when set.

The published `config/bachs.php` also supports multiple named connections:

```php
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

// Or use the global helper:
$client = bachs();
```

### Products

```php
use OkekeDev\Bachs\Resources\Products;

$products = Products::list(['limit' => 10]);
$product = Products::create([
    'name' => 'Pro Plan',
    'description' => 'Access to all features',
    'price' => ['amount' => '29.99', 'currency' => 'USD'],
]);

Products::update($product->id(), ['name' => 'Pro Plan (Annual)']);
Products::archive($product->id());
Products::unarchive($product->id());
```

### Customers

```php
use OkekeDev\Bachs\Resources\Customers;

$customer = Customers::create([
    'email' => ' customer@example.com',
    'name' => 'Jane Doe',
]);

Customers::update($customer->id(), ['name' => 'Jane Smith']);
$portalSession = Customers::createPortalSession($customer->id());
$url = $portalSession->url();
```

### Billable trait

Add the `Billable` trait to any Eloquent model (e.g. `User`) to get
billing helpers:

```php
use OkekeDev\Bachs\Concerns\Billsable;

class User extends Authenticatable
{
    use Billsable;
}
```

```php
// Create a Bachs customer from the model
$user->createAsBachsCustomer();

// Checkout
$session = $user->checkout([
    'product_cart' => [['product_id' => 'prod_xxx', 'quantity' => 1]],
]);
return redirect($session->url());

// Subscriptions
$session = $user->subscribeTo('prod_xxx');
$user->subscribed();  // bool
$user->subscription(); // ?Subscription
$user->cancel();
$user->resume();

// Billing portal
return redirect($user->billingPortalUrl());
```

### Checkout

```php
use OkekeDev\Bachs\Resources\CheckoutSessions;

$session = CheckoutSessions::create([
    'product_cart' => [['product_id' => 'prod_xxx', 'quantity' => 1]],
    'customer' => ['customer_id' => 'cus_xxx'],
    'success_url' => 'https://example.com/success',
    'cancel_url' => 'https://example.com/cancel',
]);

$session->url();     // redirect URL
$session->status();  // checkout status
```

### Subscriptions

```php
use OkekeDev\Bachs\Resources\Subscriptions;

$subscription = Subscriptions::get('sub_xxx');
$subscription->isActive();
$subscription->isTrialing();
$subscription->isPastDue();
$subscription->isCanceled();

Subscriptions::cancel('sub_xxx');
Subscriptions::update('sub_xxx', ['cancel_at_period_end' => true]);
```

### Payments & refunds

```php
use OkekeDev\Bachs\Resources\Payments;
use OkekeDev\Bachs\Resources\Refunds;

$payments = Payments::list(['limit' => 10]);
$payment = Payments::get('pay_xxx');
$payment->isSucceeded();
$payment->isRefundable();

// Full refund
$refund = $payment->refund();

// Partial refund via the Refunds resource
$refund = Refunds::create([
    'payment_id' => 'pay_xxx',
    'amount' => '10.00',
]);
```

### Webhooks

Register the webhook route in your `routes/web.php`:

```php
Route::post('bachs/webhook', [\OkekeDev\Bachs\Http\Controllers\WebhookController::class, '__invoke']);
```

Set the signing secret in `.env`:

```dotenv
BACHS_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxx
```

Listen for typed events in your `EventServiceProvider`:

```php
use OkekeDev\Bachs\Events\PaymentSucceeded;
use OkekeDev\Bachs\Events\SubscriptionCanceled;

protected $listen = [
    PaymentSucceeded::class => [/* ... */],
    SubscriptionCanceled::class => [/* ... */],
];
```

Enable local model sync in `config/bachs.php`:

```php
'database' => [
    'sync' => true,
],
```

### Blade components

```blade
{{-- Hosted checkout redirect --}}
<x-bachs::checkout
    product="prod_xxx"
    email="customer@example.com"
    success-url="https://example.com/success"
    cancel-url="https://example.com/cancel"
    class="btn btn-primary"
>
    Subscribe Now
</x-bachs::checkout>

{{-- Inline overlay modal --}}
<x-bachs::checkout-overlay
    product="prod_xxx"
    email="customer@example.com"
/>

{{-- Subscription checkout --}}
<x-bachs::subscribe
    product="prod_xxx"
    email="customer@example.com"
/>
```

### Artisan commands

```bash
# Publish config, migrations, and views
php artisan bachs:install

# Verify API connectivity
php artisan bachs:health

# Webhook management
php artisan bachs:webhook:test https://example.com/webhook
php artisan bachs:webhook:list
php artisan bachs:webhook:inspect evt_xxx
php artisan bachs:webhook:replay evt_xxx
```

### Low-level transport

`BachsClient` is the HTTP transport used by the resource layer. It handles
authentication, JSON, timeouts, idempotency keys, custom headers, retries with
exponential backoff, and typed exceptions:

```php
$response = $client->get('products', ['limit' => 20]);
$response->status();      // 200
$response->json();        // decoded payload
$response->json('price.amount', '0.00'); // dot-notated access
$response->toArray();     // decoded payload as an array
$response->requestId();   // x-request-id, for support

$client->post('customers', ['email' => 'a@b.com'], 'idem_123');
```

- **Retries:** safe methods (`GET`/`HEAD`/`OPTIONS`) and requests carrying an
  `Idempotency-Key` retry on 429/5xx and network failures. The delay grows
  exponentially and a 429's `Retry-After` is honored.
- **Headers:** connection-level defaults plus per-request headers.
  `Authorization`, `Accept`, `Content-Type`, and `Idempotency-Key` are
  reserved and cannot be overridden.
- **Exceptions:** non-2xx responses throw typed exceptions —
  `BachsAuthenticationException` (401), `BachsValidationException` (422),
  `BachsNotFoundException` (404), `BachsRateLimitException` (429),
  `BachsConflictException` (409), `BachsNetworkException`, and the base
  `BachsApiException`.

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

The MIT License (MIT). Please see [`LICENSE`](LICENSE) for more
information.
