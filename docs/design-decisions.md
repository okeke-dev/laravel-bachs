# Design Decisions

> Recorded decisions and their rationale for **laravel-bachs**. New decisions append to the end.

---

## D-01. Money is always a decimal string; never float

**Decision:** Amounts are modeled as a `Money` value object backed by a string, mirroring the Bachs API's decimal-string contract exactly. The package rejects floats at the boundary (`BachsInvalidArgumentException`).

**Why:** The Bachs spec is explicit: amounts are decimal strings at the currency's precision and must never be sent as numbers or minor units. PHP floats cannot represent money safely, and silently converting would corrupt the API contract (e.g. `29.10` as a float). String arithmetic avoids rounding entirely.

**Trade-offs:** DTO consumers get strings unless they call `Money::format()`. This is the price of correctness and matches the upstream contract.

---

## D-02. Webhook signature is `HMAC-SHA256("{timestamp}.{raw_body}")`, verified before parsing

**Decision:** The `SignatureVerifier` reconstructs the signed message from `X-Bachs-Timestamp` + the raw request body, computes the HMAC with the configured secret, and compares with `hash_equals`. A configurable tolerance window (default 300s) rejects stale/replay deliveries. The raw body is captured before any JSON parsing.

**Why:** This is the scheme the official docs specify. The existing `kodedjackson/bachs-laravel` package signs only the raw body (no timestamp) and reads a non-standard header — against the documented spec, its verification fails. We implement the documented scheme, not the reference package's.

---

## D-03. Laravel HTTP client as the transport; SDK stays framework-free above it

**Decision:** The transport uses Laravel's `Http` client. The resource/DTO/exception layers never reference Laravel facades or the container.

**Why:** Reusing Laravel's HTTP client (Guzzle-backed) gives us middleware, fakes, retries, and connection management for free, and matches how Laravel developers already test. Keeping upper layers framework-independent preserves the option of extracting a standalone PHP SDK later (future ecosystem goal) without a rewrite.

---

## D-04. No `BachsResource` mega-class; one class per resource

**Decision:** Each Bachs resource maps to one class (`Customers`, `Products`, `CheckoutSessions`, ...) with `create/get/list/update/...` methods returning DTOs.

**Why:** Mirrors the API's resource orientation, stays small and discoverable, and gives a clean seam for Phase-2 resources (Connect, Payouts) without touching core classes. A generic "resource base" class is kept minimal (shared request execution), not a swiss-army abstraction.

---

## D-05. `Webhooks/` namespace split: delivery primitives vs. management API

**Decision:** Delivery primitives (`SignatureVerifier`, `WebhookProcessor`, `WebhookEvent`) live in `src/Webhooks/`; the management/resource class is `src/Resources/Webhooks.php`.

**Why:** The prompt's suggested tree puts a single `Resources/Webhooks.php` next to a `Webhooks/` folder of delivery helpers — the class and namespace would collide under PSR-4. Splitting keeps PSR-4 clean and separates two genuinely different concerns (inbound processing vs. the Bachs webhook-endpoint management API).

---

## D-06. Subscriptions are created through checkout only — no fake create endpoint

**Decision:** There is no `Subscriptions::create()`. `$user->subscribeTo($productId)` creates a checkout session for a recurring product and returns the `CheckoutSession` DTO; the subscription materializes when Bachs completes the checkout.

**Why:** Bachs explicitly has no `POST /v1/subscriptions`. Inventing a create endpoint would violate the API contract and mislead developers. The package's job is to make the real flow ergonomic, not to fabricate the API.

---

## D-07. Idempotency is opt-in per request via a fluent header helper

**Decision:** `withIdempotencyKey($key)` on resources (e.g. `Bachs::checkoutSessions()->withIdempotencyKey($key)->create(...)`). Auto-retry of mutations only happens when an idempotency key is present or explicitly requested.

**Why:** Bachs caches idempotency responses for 24h and returns `409 IDEMPOTENCY_CONFLICT` when a key is reused with a different body. Blind retries of POST/PATCH without a key can double-charge. Making it explicit keeps behavior predictable and safe.

---

## D-08. Local DB models are optional mirrors; Bachs is the source of truth

**Decision:** Models (`BachsCustomer`, `BachsPayment`, ...) and their migrations are publishable and sync is opt-in (`bachs.database.sync`). Webhooks update local rows; local rows never drive billing decisions.

**Why:** Matches Cashier's philosophy and the API's own guidance ("treat webhooks as the source of truth"). Avoiding bidirectional sync prevents the local DB from becoming a source of drift or a billing authority.

---

## D-09. Typed Laravel events for every documented Bachs event type

**Decision:** One event class per webhook event type (27 total incl. Connect) plus a universal `WebhookReceived`. Events carry typed payloads (DTOs where a resource exists, else the raw `data` array wrapped safely).

**Why:** Developers want `Event::listen(fn (PaymentSucceeded $e) => ...)` ergonomics and static analysis. We only create events for events present in the official `event_types` enum — no invented events.

---

## D-10. Acknowledge webhooks before heavy work by default

**Decision:** The controller returns `2xx` as soon as the payload is verified and deduped; processing (event dispatch, model sync) runs synchronously unless queueing is enabled, in which case it is pushed to the queue after ack. No slow application work precedes the ack unless `webhook.ack_before_processing = false`.

**Why:** Bachs retries failed deliveries; a slow handler risks duplicate deliveries and timeouts. Ack-first behavior plus `evt_`-id dedupe makes the pipeline retry-safe.

---

## D-11. Exception hierarchy keyed on status + `error_code`

**Decision:** A `Map` converts `(status, error_code)` → specific exception subclass; every `BachsApiException` carries status, `error_code`, message, `x-request-id`, field errors, `details`, and `doc_url`. Secrets are never included.

**Why:** The API exposes both HTTP status and a stable machine-readable `error_code`; branching on either alone loses information. Typed exceptions let callers `catch (BachsRateLimitException $e)` and inspect `Retry-After`.

---

## D-12. PHP/Laravel support matrix decided by reality, not aspiration

**Decision:** Target PHP `^8.2` and Laravel `^11.0|^12.0` for v1.0, validated by a CI matrix. Support is expanded only for versions CI actually tests.

**Why:** Bachs' own keys/scopes and sandbox imply a modern stack; supporting legacy Laravel costs CI time and code forks. The existing Koded package requires `^8.2` + `^10|^11|^12|^13`; we validate rather than blindly match. Final versions confirmed in milestone 1 against current Laravel support policy.

---

## D-13. Base URL derived from environment/key prefix with explicit override

**Decision:** `bachs.env` (`sandbox` | `live` | `custom`). Default base URL is derived from the key prefix (`sk_sandbox_` → sandbox, otherwise live), and can be overridden via `BACHS_BASE_URL`.

**Why:** Prevents the classic mistake of pointing a sandbox key at production. The prefix rule is a documented Bachs convention; the explicit override covers self-hosted/custom endpoints.

---

## D-14. Marketplace ships as a separate coherent domain after 1.0

**Decision:** Connect/Connected Accounts/Payouts/Transfers/Disputes/Conversions are milestone 16, after core billing is stable. They are implemented as distinct resources, not bolted onto billing classes.

**Why:** Mixing marketplace concepts into ordinary billing classes would bloat the core API. A separate domain keeps each surface coherent and lets 1.0 land focused and tested.

---

## D-15. Every documented feature ships with a test

**Decision:** New code is accepted only with tests for the happy path and failure paths (HTTP fakes, webhook fixture payloads, signature vectors, duplicate-event cases). Bug reports must add a regression test.

**Why:** Payment code cannot be verified by eyeballing. This is a hard gate for the package's quality bar ("would a developer trust this in production?").

---

## D-16. Retries back off exponentially and honor `Retry-After`

**Decision:** The transport retries with a delay that grows exponentially (`retry.sleep_ms` base, `retry.multiplier` growth, `retry.max_sleep_ms` cap). A 429 response carrying `Retry-After` (or `X-RateLimit-Reset`) is honored verbatim instead of the computed backoff. The delay is computed in a pure `Support\RetryDelay` helper so it is unit-testable without HTTP.

**Why:** Bachs explicitly provides rate-limit timing headers; a fixed 100ms sleep is neither polite under sustained throttling nor fast enough for brief 5xx blips. Exponential growth bounds total retry time while keeping early retries snappy. Honoring `Retry-After` respects the API's own signal rather than guessing.

---

## D-17. Custom headers cannot override transport-reserved headers

**Decision:** Connections may define default headers (`connections.*.headers`) applied to every request, and each request may add its own `headers` option. `Authorization`, `Accept`, `Content-Type`, and `Idempotency-Key` are reserved: caller-supplied values for these names are silently dropped so authentication and content negotiation cannot be tampered with.

**Why:** A per-connection `X-Account` or trace header is a common multi-account need, but letting callers rewrite the bearer token (or a future connection's default `Authorization`) would be a security hole. The transport owns identity; callers only decorate.

---

## D-18. API versioning lives in the base URL path, not a header

**Decision:** The `/v1` segment is configurable via `connections.*.api_version` (default `v1`) and is appended to the sandbox/live host unless an explicit `base_url` is set. No version request header is sent.

**Why:** Bachs versions its API in the URL path (`/api.bachs.io/v1`), and research found no documented version header. Inventing `X-Bachs-Api-Version` would violate the spec; making the segment configurable future-proofs against a v2 without breaking the default.

---

## Open questions / to verify in M1

- Exact current Laravel (11/12/13?) and PHP (8.2/8.3/8.4?) support policy snapshot for the CI matrix.
- Whether `paused` subscription status and `interval_count`/`frequency` naming divergences are settled upstream (see `research.md` §9).
- Packagist namespace: `laravel-bachs` vs `bachs/laravel-bachs` — decide with maintainer before publishing.
