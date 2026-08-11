# Architecture

> Layered design for **laravel-bachs** — an independent, community-maintained Laravel integration for Bachs.io.

---

## 1. Layering principle

The package is split so the PHP SDK is framework-independent at its core, with Laravel-specific behavior layered on top. Nothing below the `Laravel` layer may call Laravel facades, `config()`, or `Container::getInstance()`.

```
┌────────────────────────────────────────────────────────────────────┐
│ Bachs API (https://api.bachs.io/v1 · https://sandbox-api.bachs.io/v1) │
└────────────────────────────────────────────────────────────────────┘
                                 ▲
┌────────────────────────────────┴────────────────────────────────────┐
│ HTTP TRANSPORT — Laravel's HTTP client (PSR-18 compatible surface)   │
│   auth header, base URL, JSON, timeouts, retries, Idempotency-Key,   │
│   rate-limit/backoff, logging, x-request-id capture                   │
└──────────────────────────────────────────────────────────────────────┘
                                 ▲
┌────────────────────────────────┴──────────────────────────────────────┐
│ SDK / RESOURCE LAYER — framework-independent                          │
│   BachsClient + per-resource classes (Customers, Products, ...)       │
│   → maps to/from typed DTOs; throws typed Exceptions                  │
└───────────────────────────────────────────────────────────────────────┘
                                 ▲
┌────────────────────────────────┴───────────────────────────────────────┐
│ DTOs · VALUE OBJECTS · EXCEPTIONS                                       │
│   Customer, Product, Payment, Refund, Subscription, CheckoutSession,    │
│   Money, Currency, BachsException hierarchy                             │
└─────────────────────────────────────────────────────────────────────────┘
                                 ▲
┌────────────────────────────────┴─────────────────────────────────────────┐
│ LARAVEL INTEGRATION — service provider, facade, config, HTTP bindings    │
│   BachsServiceProvider · Bachs facade · BachsManager (multi-account)     │
└──────────────────────────────────────────────────────────────────────────┘
                                 ▲
┌────────────────────────────────┴──────────────────────────────────────────┐
│ BILLABLE · CUSTOMER SYNC · LOCAL MODELS                                   │
│   Concerns\Billable · Models\BachsCustomer, BachsSubscription, ...        │
│   optional DB mirrors, kept in sync by webhooks (Bachs = source of truth) │
└───────────────────────────────────────────────────────────────────────────┘
                                 ▲
┌────────────────────────────────┴──────────────────────────────────────────┐
│ WEBHOOKS · EVENTS · QUEUES                                                │
│   SignatureVerifier · WebhookProcessor · WebhookEvent · typed Events      │
│   optional event store with evt_ id dedupe, queued + retry-safe           │
└───────────────────────────────────────────────────────────────────────────┘
                                 ▲
┌────────────────────────────────┴───────────────────────────────────────────┐
│ PRESENTATION & TOOLING                                                     │
│   Blade components · Artisan commands · testing helpers                     │
└─────────────────────────────────────────────────────────────────────────────┘
```

Each layer may only depend on the layer directly beneath it.

---

## 2. Directory layout

```
laravel-bachs/
├── composer.json
├── config/bachs.php
├── database/migrations/            # publishable, reversible
├── resources/views/components/     # Blade checkout components
├── routes/web.php                  # webhook route (configurable path)
├── src/
│   ├── BachsServiceProvider.php
│   ├── BachsManager.php            # multi-key / multi-account
│   ├── BachsClient.php             # transport facade (Laravel Http)
│   ├── Contracts/                  # extensibility seams
│   ├── Resources/
│   │   ├── Customers.php
│   │   ├── Products.php
│   │   ├── CheckoutSessions.php
│   │   ├── Payments.php
│   │   ├── Refunds.php
│   │   ├── Subscriptions.php
│   │   ├── Webhooks.php            # management API + delivery primitives
│   │   ├── Balances.php
│   │   ├── Currencies.php
│   │   ├── PaymentMethods.php
│   │   └── (Phase 2) ConnectedAccounts.php, Transfers.php, Payouts.php, ...
│   ├── Dto/                        # immutable-ish, typed
│   ├── ValueObjects/
│   │   ├── Money.php               # decimal-string backed, never float
│   │   └── Currency.php
│   ├── Exceptions/                 # hierarchy, see §6
│   ├── Events/                     # typed Laravel events (M 11)
│   ├── Webhooks/
│   │   ├── SignatureVerifier.php
│   │   ├── WebhookProcessor.php
│   │   └── WebhookEvent.php
│   ├── Http/Controllers/WebhookController.php
│   ├── Models/                     # optional local mirrors
│   ├── Concerns/Billable.php
│   ├── Console/Commands/
│   ├── Facades/Bachs.php
│   └── View/Components/
├── tests/                          # Unit · Feature · (Integration opt-in)
├── docs/
├── .github/workflows/ci.yml
└── pint.json
```

Deviation note vs. the prompt's suggested tree: resources live under `Resources/` (camel-case classes, plural) rather than a flat `Resources/` with a `Webhooks.php` doubling as delivery + management; delivery primitives live in `Webhooks/` (lowercase) to avoid a class/namespace clash. Rationale recorded in `design-decisions.md`.

---

## 3. HTTP transport (`BachsClient`)

- Built on Laravel's HTTP client (`Illuminate\Support\Facades\Http`), which is Guzzle-based.
- Centralizes: `Authorization: Bearer <key>`, base URL (env-aware sandbox/live), `Accept`/`Content-Type`, timeout, connect timeout, retries with exponential backoff, `Idempotency-Key`, custom headers, rate-limit header reading, and safe logging.
- Captures `x-request-id` from every response for exceptions and logs.
- Base URL auto-selection: config `bachs.env` (`sandbox`|`live`); default base URL derived from key prefix (`sk_sandbox_` → sandbox, else live) with an explicit `base_url` override. The API version segment is configurable via `api_version` (default `v1`) — versioning lives in the URL path, never a header.
- Requests go through a `BachsRequest` value object (method, path, query, body, headers, idempotency key) so middleware/retries act on one shape.

### Retries
- Configurable: `retry.times`, `retry.sleep_ms` (base), `retry.multiplier`, `retry.max_sleep_ms`, `retry.when` (default: 429 + 5xx + network/timeout).
- Delay grows exponentially (`sleep_ms * multiplier^(attempt-1)`, capped at `max_sleep_ms`). A 429 response's `Retry-After` (or `X-RateLimit-Reset`) is honored verbatim — see `Support\RetryDelay`.
- `POST`/`PATCH` requests only auto-retry when an `Idempotency-Key` is present OR the call opts in explicitly. Never blind-retry a mutation.

### Headers
- Connections may set default headers (`connections.*.headers`) applied to every request; per-request `headers` (via the request `options`) merge on top.
- `Authorization`, `Accept`, `Content-Type`, and `Idempotency-Key` are reserved — caller-supplied values for them are dropped so auth and content negotiation can't be overridden (see D-17).

---

## 4. Resource layer

- One class per resource (`Products`, `Customers`, `CheckoutSessions`, ...). Methods accept scalar/id params + input arrays and return a single payload (`array`) or a `PaginatedCollection` (list). DTO returns arrive in milestone 4.
- Resources are **static entry points on the default connection** (see D-19): `Products::create([...])`, `Products::list()`, ... run through the default client, which `BachsServiceProvider` seeds at boot via `BachsResource::setDefaultClient()`.
- `PaginatedCollection` wraps `items` + pagination cursor metadata and offers `hasMore()`, `nextCursor()`, `prevCursor()`, `limit()`, `offset()`, `returned()`, `total()`, and `map()` (which preserves the metadata).
- Method naming mirrors Bachs terminology and Laravel idioms: `create()`, `get()`, `list()`, `update()`, `archive()`, `unarchive()`, `cancel()`, `createPortalSession()`.
- Mutations accept an optional idempotency key: `Products::create([...], 'idem_...')` (see D-07).

### Public surface (as shipped in M3)
```php
Products::create(['name' => 'T-shirt', 'price' => ['amount' => '29.00', 'currency' => 'USD']]);

$products = Products::list(['limit' => 20]);   // PaginatedCollection
$products->count();                            // items on this page
$products->hasMore();                          // pagination metadata
$products->nextCursor();                       // cursor for the next page

Products::get('prod_abc');
Products::update('prod_abc', ['price' => ['amount' => '35.00', 'currency' => 'USD']]);
Products::archive('prod_abc');
Products::unarchive('prod_abc');
```

Per-connection access (a `Bachs::products()->...` facade surface) is deferred to the container milestone (M5) — see D-19.

---

## 5. DTOs and value objects

- **DTOs** are read-oriented, immutable where practical, constructor-filled from arrays, expose typed properties, `toArray()`, and `raw()` (the unmodified API payload) for forward-compatibility.
- **Money**: backed by a decimal string. Rejects floats. Provides `amount()` (string), `currency()`, `format()` (locale-safe via `NumberFormatter`), `isZero()`, `equals()`, `add()`/`subtract()` returning new `Money` (no rounding, string arithmetic). Never multiply/divide into silent rounding — explicit operations only.
- **Currency**: ISO 4217 wrapper with code validation and decimal-places metadata for the supported set.
- All DTOs keep string amounts; the package never coerces to `float`.

---

## 6. Exception hierarchy

```
BachsException (base)
├── BachsApiException            # 4xx/5xx responses, carries:
│   ├── status, error_code, message, requestId, raw payload,
│   ├── field errors (validation), details (limit errors), doc_url
│   ├── BachsAuthenticationException   # 401
│   ├── BachsAuthorizationException    # 403
│   ├── BachsValidationException       # 422 (+ field errors)
│   ├── BachsNotFoundException         # 404
│   ├── BachsRateLimitException        # 429 (+ Retry-After)
│   └── BachsConflictException         # 409 (idempotency conflicts)
├── BachsNetworkException         # timeouts, DNS, connection refused
└── BachsInvalidArgumentException # local usage errors (e.g. float amount)
```
Mapping table lives in `Exceptions/Map.php` (status + `error_code` → exception class). **No secret key or signing secret ever appears in exception messages or logs.**

---

## 7. Laravel integration

- `BachsServiceProvider`: merges config, binds `BachsManager` singleton, registers facade alias, loads routes, publishes migrations/config/views, registers Blade components and console commands.
- `Bachs` facade → `BachsManager`. `app('bachs')` and `bachs()` helper.
- Config (`config/bachs.php`) covers: `api_key`, `secret`, `env`, `base_url`, `api_version`, `headers`, `webhook.secret`, `webhook.path`, `webhook.queue`, `webhook.middleware`, `timeout`, `connect_timeout`, `retry` (`times`/`sleep_ms`/`multiplier`/`max_sleep_ms`), `logging.channel`, `database` (sync toggle + table names), `default_currency`, `http.middleware`.

---

## 8. Billable (`Concerns\Billable`)

Mapped strictly to verified Bachs capabilities:

| Method | Backing |
| --- | --- |
| `bachsCustomer()` / `createAsBachsCustomer()` | customers + auto-sync, metadata points at the model |
| `checkout(...)` / `subscribeTo($productId, ...)` | checkout-session create → returns `CheckoutSession` |
| `subscription()` / `subscriptions()` | subscription get/list |
| `subscribed()` / `hasActiveSubscription()` | subscription status (`active`, `trialing`, `past_due`) |
| `billingPortalUrl()` | portal-session create |
| `refund($paymentId, ...)` | refund create |

Customer ID is persisted on the billable model via a configurable column (`bachs_customer_id`). The mapping is overridable. No forced User shape.

---

## 9. Webhooks pipeline

```
POST {config webhook path}
  → SignatureVerifier (constant-time, timestamp tolerance)
  → parse raw body once
  → WebhookEvent value object (id, type, data, organization_id, account?)
  → dispatch Events\WebhookReceived (always)
  → optional: persist to bachs_webhook_events, dedupe on evt_ id
  → acknowledge 2xx (before heavy work)
  → queue WebhookProcessor (config) or run synchronously
  → map type → typed Laravel event → dispatch
  → optional: sync local models
```

- Signature: `hash_equals(hmac_sha256(secret, "{timestamp}.{raw_body}"), header)` with fresh/expiry window (default 300s), rejecting replay/stale deliveries.
- Duplicate deliveries (same `evt_` id) acknowledged but not re-processed.
- No slow work before ack unless `webhook.ack_before_processing = false` is explicitly set.

---

## 10. Local models (optional, C tier)

- `BachsCustomer`, `BachsProduct`, `BachsPayment`, `BachsSubscription`, `BachsWebhookEvent`.
- Bachs remains the source of truth; local rows are mirrors updated by webhooks. Webhook sync is opt-in (`bachs.database.sync`).
- Safe, reversible, publishable migrations with indexed `bachs_id` unique columns.

---

## 11. Security invariants

- Secrets only ever via env/config; never logged, serialized into DTOs, or placed in exceptions.
- Signature verification uses `hash_equals`; raw body captured before JSON decode.
- Webhook payloads validated against envelope shape before dispatch.
- Logs contain method, path, status, duration, `x-request-id`, `evt_` id — never auth headers or payment card data.
- `webhook.middleware` is configurable so apps can add their own (CSRF is excluded on the webhook route).
