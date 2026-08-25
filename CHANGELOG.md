# Changelog

All notable changes to **laravel-bachs** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

### Added

- **Laravel container** — `BachsServiceProvider`, `BachsManager`, `Bachs` facade,
  `bachs()` global helper, multi-connection support, and config publishing
  (`php artisan vendor:publish --tag=bachs-config`).
- **Foundation** — package skeleton with a service provider, config file, and
  a connection manager (`Bachs::connection()`).
- **Connections** — support for multiple Bachs API keys via named connections,
  with `BACHS_ENV`-driven sandbox/live base URLs and explicit URL overrides.
- **HTTP client foundation** — `BachsClient` transport built on Laravel's HTTP
  client: bearer auth, JSON, timeouts, `Idempotency-Key` support, safe retries
  (safe methods and idempotent mutations only, on 429/5xx/network failures),
  `x-request-id` capture, and safe request logging.
- **Typed exceptions** — `BachsApiException` hierarchy keyed on status
  (`401/403/404/409/422/429` → dedicated subclasses) plus
  `BachsNetworkException` and `BachsInvalidArgumentException`, mapped from the
  verified Bachs error object (`detail`, `error_code`, `doc_url`, `errors`).
- **Request/response value objects** — `BachsRequest` and `BachsResponse`
  (status, JSON, headers, request id, rate-limit metadata).
- **Test stack** — Pest + Testbench with a green baseline suite (request
  shape, idempotency, retry policy, exception mapping, base URL resolution).
- **Quality gates** — Laravel Pint (style) and PHPStan level 6 (types) wired
  into CI across PHP 8.2–8.4 and Laravel 12–13.
- **Testing hardening** — Comprehensive test coverage additions:
  - Fuzz/payload tests: empty data, deeply nested structures, Unicode, long strings, special characters, booleans, arrays, zeros, empty strings.
  - Retry behavior tests: 502/503/504 retries, non-retried status codes (400/403/404/409/422), `retry.times=0`, DELETE/PATCH retry semantics, connection failure retries, exhausted retry error details.
  - Unit tests for `BachsRequest` (safe methods, idempotency key, properties) and `BachsResponse` (status, JSON access, headers, rate limit metadata).
  - Exception class tests (`BachsApiException`, `BachsRateLimitException`, `BachsValidationException`, `BachsNetworkException`, `BachsInvalidArgumentException`).
  - CI: composer dependency caching across all jobs.
- **Retry backoff** — retries now grow exponentially (`retry.multiplier`,
  `retry.max_sleep_ms`) and honor `Retry-After` / `X-RateLimit-Reset` on 429
  (`Support\RetryDelay`).
- **Custom headers** — connection-level default headers (`connections.*.headers`)
  plus per-request `headers` on the request options; `Authorization`, `Accept`,
  `Content-Type`, and `Idempotency-Key` are reserved and cannot be overridden.
- **Configurable API version** — the base URL version segment (`v1`) is
  configurable via `connections.*.api_version` unless an explicit `base_url`
  is set.
- **Response accessors** — `BachsResponse::json('dot.path', $default)` and
  `BachsResponse::toArray()` for normalized payload access.
- **Resource layer groundwork** — `BachsResource` base that seeds the default
  connection's client for static resource calls, plus `PaginatedCollection`, a
  Laravel collection carrying Bachs pagination metadata (`hasMore()`,
  `nextCursor()`, `prevCursor()`, `limit()`, `offset()`, `returned()`,
  `total()`, and metadata-preserving `map()`).
- **Products resource** — `Products::create()`, `list()`, `get()`, `update()`,
  `archive()`, and `unarchive()` (Tier A passthrough; DTO returns arrive in
  milestone 4). Mutations accept an optional idempotency key.
- **Currencies resource** — `Currencies::supported()` and
  `Currencies::payoutSupported()`.
- **Balances resource** — `Balances::get()`.
- **PaymentMethods resource** — `PaymentMethods::list()` (paginated) and
  `PaymentMethods::rails()`.
- **Media resource** — `Media::upload()`, `get()`, and `delete()`, backed by new
  multipart upload support in the transport (`BachsClient::upload()` /
  `BachsRequest` attachments); invalid files are rejected up front with
  `BachsInvalidArgumentException`.
- **ProductGroups resource** — `ProductGroups::create()`, `list()`, `get()`,
  `update()`, and `delete()`. Mutations accept an optional idempotency key.
- **Customers resource** — `Customers::create()`, `list()`, `get()`, `update()`,
  and `createPortalSession()`. Tier A passthrough with `Customer` and
  `PortalSession` DTOs. Mutations accept an optional idempotency key.
- **Billable trait** — `Concerns\Billsable` for Eloquent models: customer
  association (`createAsBachsCustomer()`, `bachsCustomer()`, `updateBachsCustomer()`),
  billing portal (`billingPortalUrl()`), checkout (`checkout()`), and
  subscription helpers (`subscribeTo()`, `subscription()`, `subscribed()`,
  `cancel()`, `resume()`).
- **Checkout** — `CheckoutSessions` resource with `create()` and `get()`,
  `CheckoutSession` DTO with status helpers and `redirect()` method, and
  `$user->checkout()` on the Billable trait.
- **Subscriptions** — `Subscriptions` resource with `list()`, `get()`, `update()`,
  and `cancel()`. `Subscription` DTO with status helpers (`isActive()`,
  `isTrialing()`, `isPastDue()`, `isCanceled()`, `isPaused()`), billing cycle,
  and period tracking.
- **Payments** — `Payments` resource with `list()`, `get()`, and `getByCharge()`.
  `Payment` DTO with status helpers (`isSucceeded()`, `isProcessing()`,
  `isFailed()`, `isRefunded()`, `isPartiallyRefunded()`, `isExpired()`,
  `isCancelled()`, `isRefundable()`) and `refund()` shortcut method.
- **Refunds** — `Refunds` resource with `create()`, `list()`, `get()`, and
  `getByCharge()`. `Refund` DTO with status helpers (`isProcessing()`,
  `isSuccess()`, `isFailed()`) and fee tracking.
- **Webhooks (delivery)** — Production-grade webhook system:
  - `Webhooks\SignatureVerifier` — HMAC-SHA256 signature verification with
    constant-time comparison and replay protection via timestamp tolerance.
  - `Webhooks\WebhookEvent` — Parsed event envelope DTO.
  - `Webhooks\WebhookProcessor` — Event identification, persistence, and
    duplicate detection with typed Laravel event dispatch.
  - `Webhooks\ProcessWebhookJob` — Queue-safe job for async processing.
  - `Http\Controllers\WebhookController` — Route handler with immediate
    acknowledgment (200) before processing.
  - 25 typed Laravel events for all Bachs webhook event types.
  - Configurable route path, middleware, queue connection, and tolerance.
  - Safe logging (never logs secrets or sensitive data).
- **Webhook persistence & idempotency** — Production-grade event storage:
  - Publishable migration for `bachs_webhook_events` table
    (`php artisan vendor:publish --tag=bachs-migrations`).
  - Event deduplication via `event_id` unique constraint with duplicate
    detection in `WebhookProcessor` (skips re-dispatch for known events).
  - Queue name configuration (`BACHS_WEBHOOK_QUEUE_NAME`) for separating
    webhook processing from other queued work.
  - `ProcessWebhookJob` unique ID for queue-level deduplication, retry-safe
    processing with configurable attempts and backoff.
  - `WebhookController` acknowledges delivery (200) before dispatching to
    queue, preventing timeout-driven retries from Bachs.
- **Artisan tooling** — Production-ready CLI commands:
  - `bachs:install` — Publish config, migrations, and views in one step
  - `bachs:health` — Verify API connectivity, config, and database status
  - `bachs:webhook:test` — Send test webhook events to a URL
  - `bachs:webhook:list` — List stored webhook events with filters
  - `bachs:webhook:inspect` — Inspect a specific webhook event payload
  - `bachs:webhook:replay` — Replay a stored webhook event
- **Local synchronized models** — Opt-in database mirrors for billing resources:
  - `BachsCustomer`, `BachsProduct`, `BachsPayment`, `BachsSubscription` Eloquent models
  - Publishable migrations (`php artisan vendor:publish --tag=bachs-migrations`)
  - Configurable table names and database connection (`bachs.database.*`)
  - Webhook-driven sync via `WebhookSyncer` — upserts on every relevant event
  - Status helper methods (`isActive()`, `isTrialing()`, `isCanceled()`, etc.)
  - JSON column casting, model relations, and PHPDoc annotations
  - Container singletons (`bachs.customer`, `bachs.product`, etc.)
  - Full test coverage (13 tests, 48 assertions)
- **Blade integration** — Accessible, unstyled Blade components for checkout:
  - `<x-bachs::checkout>` — Hosted checkout redirect link with product,
    customer/email, success/cancel URL support.
  - `<x-bachs::checkout-overlay>` — Inline modal checkout with iframe,
    ARIA dialog attributes, and close button.
  - `<x-bachs::subscribe>` — Subscription-specific checkout with
    session_mode PAYMENT.
  - All components: semantic HTML, ARIA roles/labels, configurable CSS
    classes, slot content support.
  - Publishable views via `php artisan vendor:publish --tag=bachs-views`.

### Fixed

- **CI** — Laravel 13 test rows now run on PHP 8.4 (Pest 5 and
  `pest-plugin-laravel` v5 require PHP >= 8.4), and `orchestra/testbench` is
  pinned with `--dev` so it stays in `require-dev` instead of leaking into the
  runtime dependencies.
- **Composer constraints** — widened to `pestphp/pest: ^3.8|^5.0` and
  `pestphp/pest-plugin-laravel: ^3.2|^5.0` so Laravel 12 uses Pest 3 and
  Laravel 13 uses Pest 5.

_No stable API is offered yet. Everything above is subject to change while the
package is in early development (`0.x`)._
