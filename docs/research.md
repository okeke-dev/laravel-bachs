# Research

> Reconnaissance report for the **Laravel Bachs** package — an independent, community-maintained Laravel integration for Bachs.io.
>
> Last updated: 2026-08-09. Sources are the official Bachs documentation (docs.bachs.io), the machine-readable OpenAPI spec (`/docs/openapi/openapi.json`), and public reference packages.

---

## 1. What Bachs is

Bachs is a payments and billing platform for African internet businesses selling subscriptions, SaaS, and digital products to global customers.

- **Payment collection** — cards (135+ currencies), mobile money (GHS, KES, UGX, XAF, XOF, RWF, ZMW, TZS), crypto stablecoins (USDT, USDC), and bank transfer, all in one hosted or overlay checkout.
- **Recurring billing** — subscriptions, usage billing, trials, proration, and automated payment recovery ("dunning").
- **Settlement** — merchants settle in NGN, GHS, KES, or USD to a local bank account in real time.
- **Tax** — automated VAT/GST calculation surfaced as filing obligations.
- **Connect** — marketplaces: connected accounts, onboarding, capabilities, tasks, transfers, withdrawals, and split payments.

Bachs is an early-stage African fintech. This package is **not** officially affiliated, endorsed, or sponsored by Bachs.

---

## 2. API fundamentals (verified against docs + OpenAPI spec)

| Topic | Fact |
| --- | --- |
| Base URL — production | `https://api.bachs.io/v1` |
| Base URL — sandbox | `https://sandbox-api.bachs.io/v1` |
| Authentication | `Authorization: Bearer sk_live_...` (production) / `sk_sandbox_...` (sandbox) |
| Key scopes | `<resource>:<action>` e.g. `customers:read`, `customers:write`, `products:read`, `products:write`, `payments:read`, `payments:write`, `refunds:read`, `refunds:write`, `webhooks:read`, `webhooks:write`, `disputes:*`, `payouts:*` |
| Money | **Decimal string at currency precision** (`"29.00"`), always paired with an ISO 4217 `currency`. **Never minor units. Never a float.** |
| Timestamps | ISO 8601 UTC, e.g. `2026-04-27T12:00:00Z` |
| IDs | Opaque prefixed strings: `cust_` customer, `prod_` product, `sub_` subscription, `chk_` checkout, `inv_` invoice, `ref_` refund, `pay_` payment, `chr_` charge, `pm_` payment method, `il_` line item, `whe_` webhook endpoint, `evt_` webhook event, `org_` organization |
| Content types | JSON in/out (`Content-Type: application/json`, `Accept: application/json`) |
| Rate limits | Production 500 req/min per key; sandbox 100 req/min per key. Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`; `Retry-After` on 429 |
| Request ID | Every response carries `x-request-id` — log it and surface it for support |
| List responses | `{ "items": [...], "pagination": {...} }` |
| Pagination | `limit` (default 20, max 100, clamped), `cursor` (takes precedence over `offset`), `offset`. Pagination object: `next_cursor`, `prev_cursor`, `has_more`, `limit`, `offset`, `returned`, `total`. Cursors are opaque. |
| Idempotency | `Idempotency-Key` header on all `POST`/`PATCH`. Cached 24h per key; cached on 2xx only. Reusing a key with a different body → `409 IDEMPOTENCY_CONFLICT`. |
| Webhooks | Signed. `X-Bachs-Timestamp` (unix seconds) + `X-Bachs-Signature` = HMAC-SHA256 hex of `"{timestamp}.{raw_body}"`. At-least-once delivery; dedupe on event `id`. |

### HTTP status codes

`200` OK, `201` Created, `204` No Content, `400` Bad Request, `401` Unauthorized, `403` Forbidden, `404` Not Found, `409` Conflict, `422` Validation, `428` Precondition Required, `429` Rate Limited, `500`/`502`/`503` Server errors.

### Error object

```json
{
  "detail": "Missing required field(s): name, price",
  "error_code": "VALIDATION_ERROR",
  "doc_url": "https://docs.bachs.io/api-reference/error-reference#general",
  "errors": [ { "field": "amount", "message": "...", "type": "value_error" } ],
  "details": { ... }
}
```

- `error_code` is stable and machine-readable — branch on it, never on `detail`.
- `errors[]` present only on validation errors (`VALIDATION_ERROR`, 422).
- `details` present on limit errors (deposit limits, withdrawal limits) with structured context.
- Error codes include: `BAD_REQUEST`, `UNAUTHORIZED`, `FORBIDDEN`, `NOT_FOUND`, `CONFLICT`, `VALIDATION_ERROR`, `VALIDATION_FAILED`, `TOO_MANY_REQUESTS`, `PRECONDITION_REQUIRED`, `TOTP_STEP_UP_REQUIRED`, `IMMUTABLE_FIELD`, `INTERNAL_SERVER_ERROR`, `NOT_IMPLEMENTED`, `BAD_GATEWAY`, `SERVICE_UNAVAILABLE`, plus domain codes (`PRODUCT_NOT_FOUND`, `SUBSCRIPTION_ALREADY_CANCELED`, `DEPOSIT_LIMIT_EXCEEDED`, `IDEMPOTENCY_CONFLICT`, etc.).

---

## 3. Endpoint surface (from the OpenAPI spec)

### Core billing

| Resource | Endpoints |
| --- | --- |
| Customers | `GET/POST /v1/customers`, `GET/PATCH /v1/customers/{id}`, `POST /v1/customers/{id}/portal-sessions` |
| Products | `GET/POST /v1/products`, `GET/PATCH /v1/products/{id}`, `POST /v1/products/{id}/archive`, `POST /v1/products/{id}/unarchive` |
| Product groups | `GET/POST /v1/product-groups`, `GET/PATCH/DELETE /v1/product-groups/{id}` |
| Checkout sessions | `POST /v1/checkout-sessions`, `GET /v1/checkout-sessions/{id}`, `GET /v1/checkouts/{id}` |
| Payments | `GET /v1/payments`, `GET /v1/payments/{id}`, `GET /v1/payments/charges/{charge_id}` |
| Payment methods | `GET /v1/payment-methods`, `GET /v1/payment-methods/rails` |
| Refunds | `POST /v1/refunds`, `GET /v1/refunds`, `GET /v1/refunds/{id}`, `GET /v1/refunds/by-charge/{payment_id}` |
| Subscriptions | `GET /v1/subscriptions`, `GET/PATCH/DELETE /v1/subscriptions/{id}` |
| Currencies | `GET /v1/currencies/supported`, `GET /v1/currencies/payout-supported` |
| Balances | `GET /v1/accounts/balances` |

### Disputes, conversions, payouts

| Resource | Endpoints |
| --- | --- |
| Disputes | `GET /v1/disputes`, `GET /v1/disputes/{id}`, `PATCH /v1/disputes/{id}/evidence`, `POST /v1/disputes/{id}/submit`, `POST /v1/disputes/uploads` |
| Conversions | `GET /v1/conversions`, `GET /v1/conversions/{id}`, `POST /v1/conversions`, `POST /v1/conversions/quotes` |
| Payouts / withdrawals | `POST /v1/payouts/withdrawals`, `GET /v1/payouts`, `GET /v1/payouts/{id}`, `GET /v1/payouts/banks`, `GET /v1/payouts/destinations`, `POST /v1/payouts/destinations`, `PUT/DELETE /v1/payouts/destinations/{id}`, `GET /v1/payouts/supported-currencies`, `POST /v1/payouts/quotes`, `POST /v1/payouts/resolve-account` |

### Webhooks (management API)

`GET/POST /v1/webhooks/endpoints`, `GET/PATCH/DELETE /v1/webhooks/endpoints/{id}`, `GET /v1/webhooks/endpoints/{id}/events`, `GET /v1/webhooks/endpoints/{id}/events/{event_id}`, `GET /v1/webhooks/endpoints/{id}/metrics`, `GET /v1/webhooks/endpoints/{id}/secret`, `POST /v1/webhooks/endpoints/{id}/events/{event_id}/resend`, `POST /v1/webhooks/endpoints/{id}/rotate-secret`, `GET /v1/webhooks/events`, `GET /v1/webhooks/events/{id}`, `POST /v1/webhooks/replay`

### Connect / marketplace

| Resource | Endpoints |
| --- | --- |
| Connected accounts | `GET/POST /v1/organizations/connected-accounts`, `GET/PATCH /v1/connected-accounts/{id}`, `GET .../capabilities`, `GET .../requirements/banks`, `GET .../requirements/momo`, `GET .../requirements/checklist`, `GET .../requirements/tasks`, `GET .../requirements/values`, `GET .../requirements/reusable-identity`, `POST .../requirements/accounts/resolve`, `POST .../requirements/reusable-identity/apply`, `POST .../requirements/submit`, `POST .../account-links`, `GET/POST .../uploads`, `GET .../uploads/{upload_id}` |
| Transfers | `GET/POST /v1/transfers`, `GET /v1/transfers/{id}` |

### Other

| Resource | Endpoints |
| --- | --- |
| Organizations | `GET /v1/organizations/me`, `GET /v1/organizations/{id}`, `GET/PUT /v1/organizations/checkout/settings` |
| Media / uploads | `POST /v1/utilities/uploads`, `GET/DELETE /v1/utilities/uploads/{id}` |

---

## 4. Key object shapes (verified)

### Checkout session (create request)

- Requires `customer` (either `{ customer_id }` for an existing customer, or `{ email, name, phone_number? }` for a new one).
- Pricing source, **exactly one**:
  - `product_cart`: array of `{ product_id, quantity?, amount?, pricing? }`, 1–20 items. All products must share the same primary currency.
  - `pricing` (MerchantIntent): `{ currency, amount?, price_type? (fixed|custom|free), preset_amount?, minimum_amount?, maximum_amount?, currency_options? }`.
- Other fields: `success_url` (primary; `return_url` is a deprecated alias), `cancel_url`, `billing_currency`, `allowed_payment_method_types` (`card`, `crypto`, `bank_transfer`, `mobile_money`), `reference` (≤128 chars, unique per org), `metadata` (≤20 keys, ≤10 KB), `expires_in_minutes` (1–1440, default 60).
- Create response: `{ checkout_id, checkout_url, status: OPEN, expires_at, created_at }`.
- Checkout session object adds: `payment_status`, `source_type`, `amount`, `currency`, `charge` (nullable `PaymentResponse`), `customer`, `products` (resolved line items), `session_mode` (`CART`|`SELECTION`), `recurring`.

### Customer

`customer_id`, `email`, `name`, `phone_number` (E.164), `metadata`, `billing_address`, `created_at`, `updated_at`. Create requires only `email`.

### Product

`id` (`prod_`), `organization_id`, `name`, `description`, `price` (primary `PriceResponse`), `status` (`active`|`archived`), `metadata`, `media`, `created_at`, `updated_at`, `archived_at`, `billing_cycle` (`{ interval: day|week|month|year, frequency }` — **immutable once set**), `trial_period`, `prices[]`, `total_payments`, `total_amount`.

### Payment

`payment_id` (`pay_`), `charge_id` (`chr_`), `reference`, `billing_reason` (`purchase`, `subscription_create`, `subscription_cycle`, `subscription_update`), `checkout_id`, `status` (`created`, `processing`, `succeeded`, `accepted`, `failed`, `expired`, `cancelled`, `refunded`, `partially_refunded`, `underpaid`, `overpaid`), `is_refundable`, `amount`, `amount_paid`, `amount_remaining`, `currency`, `fee_usd`, `payment_method`, `line_items`, `subscription_id`, `invoice`, `refunds[]`, `status_history[]`, timestamps.

### Refund

`refund_id` (`ref_`), `charge_id`, `reference`, `status` (`processing`|`success`|`failed`), `requested_amount`, `refunded_amount`, `refund_fee_amount`, `fee_bearer` (`org`|`customer`), `reason`, timestamps. **Only one refund per charge.** Refunds settle asynchronously (`refund.*` webhooks).

### Subscription

`id` (`sub_`), `customer` (nested object), `payment_method_id`, `status` (`trialing`, `active`, `past_due`, `unpaid`, `canceled`, `paused`), `collection_method` (`charge_automatically`), `currency`, `amount` (decimal string), `billing_cycle`, `quantity`, `current_period_start/end`, `previously_billed_at`, `next_billed_at`, `trial_end`, `cancel_at_period_end`, `canceled_at`, `product`, `items[]`, `metadata`.

**Critical fact:** subscriptions are created only by completing a checkout for a recurring product. **There is no `POST /v1/subscriptions`.** Subscriptions are USD-card-only today.

Subscription update sends **exactly one intent**: change plan (`product_id`), move trial (`trial_end`), change payment method (`payment_method_id`), or update metadata. Optional `proration_behavior` (`invoice_now` | `next_cycle` | `none`, default `invoice_now`). Cancellation via `DELETE` (immediate) or `POST/PATCH` with `cancel_at_period_end` + optional `reason`.

---

## 5. Webhook system (verified)

- Delivery: `POST` to your endpoint. Headers `X-Bachs-Timestamp` (unix seconds) and `X-Bachs-Signature`.
- Signature: `hmac_sha256(secret, "{timestamp}.{raw_body}")` as lowercase hex, compared with `hash_equals`. Verify **before** parsing JSON. Stale-timestamp tolerance (300s) recommended.
- Envelope:

```json
{
  "id": "evt_...",
  "type": "collection.succeeded",
  "created_at": "2026-02-22T16:20:00.123456+00:00",
  "organization_id": "org_abc123",
  "account": "org_4d81fa9c2b6e0357",  // present only for Connect events
  "data": { ... }
}
```

- At-least-once delivery — dedupe on `id`.
- Endpoints have an `event_source`: `account` (default, own org), `connect` (connected accounts), or `all`. An endpoint created without it receives nothing about connected accounts.
- Event types (from the endpoint `event_types` enum):

  - **Checkout:** `checkout.completed`, `checkout.expired`
  - **Payments:** `collection.succeeded`, `collection.failed`, `collection.underpaid`
  - **Subscriptions:** `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`
  - **Invoices:** `invoice.created`, `invoice.paid`, `invoice.payment_failed`
  - **Payouts:** `payout.created`, `payout.paid`, `payout.failed`
  - **Refunds:** `refund.created`, `refund.paid`, `refund.failed`
  - **Disputes:** `dispute.created`, `dispute.updated`
  - **Conversions:** `conversion.completed`, `conversion.failed`
  - **Customers:** `customer.created`, `customer.updated`
  - **Connect:** `account.updated`, `capability.updated`, `transfer.created`

- Webhook management API includes replay (`POST /v1/webhooks/replay`), resend, rotate-secret, per-endpoint metrics, and event listing. Signing secret (`whsec_...`) is returned **once** on endpoint creation.

---

## 6. Existing package: `kodedjackson/bachs-laravel`

Public repo (2 commits), MIT, `illuminate/support ^10|^11|^12|^13`, PHP ^8.2. Structure: `config/bachs.php`, `src/BachsClient.php`, `src/BachsServiceProvider.php`, `src/Events/CollectionSucceeded.php`, `src/Facades/Bachs.php`, `src/Http/Controllers/WebhookController.php`.

**What it covers**
- `createCheckoutSession(array)` and `createProduct(array)` via `Http::withToken(...)->post(...)` returning raw arrays.
- `POST /bachs/webhook` route with signature verification; dispatches one event (`CollectionSucceeded`).
- Config with `public_key`, `secret_key`, `base_url`, `currency`, `success_url`, `cancel_url`, `webhook_secret`.

**What it does not cover / gaps**
- No exception hierarchy, no error mapping, no typed DTOs, no resources beyond the two methods.
- No customer, payment, refund, subscription, balance, payout, transfer, connected-account, or webhook-management APIs.
- No Billable trait, no events beyond `collection.succeeded`, no queues, no idempotency handling, no retries, no local models.
- **Webhook signature verification is inconsistent with the official spec**: it HMACs the raw body *without* the timestamp (`hash_hmac('sha256', $request->getContent(), $secret)`) and reads header `Bachs-Signature` (spec says `X-Bachs-Signature` + `X-Bachs-Timestamp`, message = `"{timestamp}.{raw_body}"`). Requests built against the documented spec would fail verification.
- No tests, no CI, no static analysis, minimal documentation.

**Conclusion:** a thin starting point and useful naming reference, but not a foundation to fork. It predates much of the current API surface and does not implement the documented webhook scheme.

---

## 7. Reference package: `unicodeveloper/laravel-paystack`

~647 stars, 162 commits. Architecture lessons to borrow (patterns, not code):

- Thin service provider binding a manager/client into the container; `Paystack` facade and `paystack()` helper for fluent access.
- Fluent authorization-object flow: `Paystack::getAuthorizationUrl($data)->redirectNow()`.
- `getPaymentData()` style verification-on-callback helpers.
- Straightforward config publishing.
- Weaknesses to avoid: no test suite worth citing, `.travis.yml` build, PHP 5.4 floor, no DTOs, no webhook framework, no static analysis.

---

## 8. Laravel Cashier philosophy (to mirror, not copy)

Cashier shows what "framework-native billing" means for Laravel:

- `Billable` model trait with expressive helpers: `checkout()`, `newSubscription()`, `subscription()`, `subscribed()`, `onTrial()`, `cancel()`, `resume()`, `billingPortalUrl()`.
- Stripe stays the source of truth; local models (`Subscription`, `Customer`, `Invoice`) are convenience mirrors kept in sync via webhooks.
- Webhook controller handles the endpoint; signature verification is exact and constant-time; events dispatch typed Laravel events.
- Queued, retry-safe webhook handling; idempotency via `stripe_id`/event identity.
- Strong docs-first developer experience.

---

## 9. Assumptions requiring verification

1. **Subscription checkout currency** — "subscriptions are USD card only today"; recurring products are priced `USD` or `NGN` per `PriceInput`, but the docs state subscriptions currently bill USD cards only. Flag as a moving target; keep config-driven, do not hardcode.
2. **`paused` status** — present in the OpenAPI enum but not in the docs status table. Expose it, but document as "per API enum; docs do not describe transitions yet".
3. **`checkout_id` format** — create response example shows a UUID-ish value (`5d7ab015-...`) while the object docs use `chk_...`. Treat IDs as opaque.
4. **Refund `fee_bearer`** — OpenAPI enum is `org|customer` but the example shows `"merchant"`. Keep the enum from the spec, map unknown values gracefully.
5. **`interval_count` vs `frequency`** — error reference uses `interval_count`; product/subscription objects use `frequency`. Mirror the object field (`frequency`) and flag the discrepancy in the package docs.
6. **Trails/beta features** — trials are "currently in beta"; several subscription/payout capabilities are `Limited Access` and require contacting Bachs. The package should not assume they are enabled.
7. **Sandbox rate limit** — 100 req/min means tests must be respectful of rate limits when hitting the real sandbox (prefer HTTP fakes).

---

## 10. Key facts the package must encode

- Money is a **decimal string**; never convert to floats; expose a `Money` value object that keeps string fidelity.
- `Idempotency-Key` support is first-class (`Idempotency-Key` header, 24h cache, `409` on mismatch).
- Webhook signature = HMAC-SHA256 of `"{timestamp}.{raw_body}"` with constant-time comparison and timestamp tolerance.
- At-least-once delivery → dedupe webhooks on `evt_` id.
- Webhooks are the **source of truth** for fulfillment; redirects are not.
- Subscriptions originate from checkouts, not a create endpoint.
- Product `billing_cycle` is immutable after creation.
- List responses are `{ items, pagination }` with opaque cursors.
