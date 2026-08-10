# API Coverage Map

> Maps every Bachs API endpoint → PHP resource → Laravel abstraction → DTO/model → tests → documentation.
> This is the contract for the package. Anything marked **Phase 2** is deferred to keep the first release focused; the low-level SDK always exposes every endpoint regardless of the Laravel abstraction tier.

Legend: ✅ = confirmed by OpenAPI spec / docs · 🟡 = limited-access / beta feature · **M** = milestone that delivers it (see `roadmap.md`).

---

## Resource tiers

| Tier | Meaning |
| --- | --- |
| **A — SDK passthrough** | `Bachs::payments()->get($id)` — typed request/response, no Laravel sugar. Every endpoint gets this. |
| **B — Laravel abstraction** | Conveniences on top: customer auto-sync, Billable, checkout redirect helpers, local models. |
| **C — Webhook-driven** | Kept in sync by webhook events, optional, off by default. |

---

## Checkout sessions

| Bachs endpoint | Resource | Laravel abstraction | DTO | M |
| --- | --- | --- | --- | --- |
| `POST /v1/checkout-sessions` | `CheckoutSessions::create()` | `Bachs::checkout()`, `$user->checkout()`, `CheckoutSession::redirect()` | `CheckoutSession` | 8 |
| `GET /v1/checkout-sessions/{id}` | `CheckoutSessions::get($id)` | — | `CheckoutSession` | 8 |
| `GET /v1/checkouts/{id}` | `Checkouts::get($id)` | — | `Checkout` | 8 |

## Customers

| Bachs endpoint | Resource | Laravel abstraction | DTO | M |
| --- | --- | --- | --- | --- |
| `POST /v1/customers` | `Customers::create()` | `$user->createAsBachsCustomer()` | `Customer` | 6 |
| `GET /v1/customers` | `Customers::list()` | — | `Customer` | 6 |
| `GET /v1/customers/{id}` | `Customers::get($id)` | `$user->bachsCustomer()` | `Customer` | 6 |
| `PATCH /v1/customers/{id}` | `Customers::update($id, ...)` | `$user->updateBachsCustomer()` | `Customer` | 6 |
| `POST /v1/customers/{id}/portal-sessions` | `Customers::createPortalSession($id)` | `$user->billingPortalUrl()` | `PortalSession` | 9 |

## Products & catalog

| Bachs endpoint | Resource | Laravel abstraction | DTO | M |
| --- | --- | --- | --- | --- |
| `POST /v1/products` | `Products::create()` | — | `Product` | 3 |
| `GET /v1/products` | `Products::list()` | — | `Product` | 3 |
| `GET /v1/products/{id}` | `Products::get($id)` | — | `Product` | 3 |
| `PATCH /v1/products/{id}` | `Products::update($id, ...)` | — | `Product` | 3 |
| `POST /v1/products/{id}/archive` | `Products::archive($id)` | — | `Product` | 3 |
| `POST /v1/products/{id}/unarchive` | `Products::unarchive($id)` | — | `Product` | 3 |
| `GET/POST /v1/product-groups`, `GET/PATCH/DELETE .../{id}` | `ProductGroups::*` | — | `ProductGroup` | 16 |
| `GET /v1/payment-methods` | `PaymentMethods::list()` | — | `PaymentMethod` | 10 |
| `GET /v1/payment-methods/rails` | `PaymentMethods::rails()` | — | `PaymentRail` | 10 |
| `POST /v1/utilities/uploads` | `Media::upload()` | — | `Upload` | 3 |
| `GET/DELETE /v1/utilities/uploads/{id}` | `Media::get()/delete()` | — | `Upload` | 3 |

## Payments

| Bachs endpoint | Resource | Laravel abstraction | DTO | M |
| --- | --- | --- | --- | --- |
| `GET /v1/payments` | `Payments::list()` | — | `Payment` | 10 |
| `GET /v1/payments/{id}` | `Payments::get($id)` | — | `Payment` | 10 |
| `GET /v1/payments/charges/{id}` | `Payments::getByCharge($id)` | — | `Payment` | 10 |
| `GET /v1/currencies/supported` | `Currencies::supported()` | — | `Currency` | 10 |
| `GET /v1/currencies/payout-supported` | `Currencies::payoutSupported()` | — | `Currency` | 16 |
| `GET /v1/accounts/balances` | `Balances::get()` | — | `Balance` | 16 |

## Refunds

| Bachs endpoint | Resource | Laravel abstraction | DTO | M |
| --- | --- | --- | --- | --- |
| `POST /v1/refunds` | `Refunds::create()` | `$payment->refund()` | `Refund` | 10 |
| `GET /v1/refunds` | `Refunds::list()` | — | `Refund` | 10 |
| `GET /v1/refunds/{id}` | `Refunds::get($id)` | — | `Refund` | 10 |
| `GET /v1/refunds/by-charge/{payment_id}` | `Refunds::getByCharge($paymentId)` | — | `Refund` | 10 |

## Subscriptions

| Bachs endpoint | Resource | Laravel abstraction | DTO | M |
| --- | --- | --- | --- | --- |
| `GET /v1/subscriptions` | `Subscriptions::list()` | — | `Subscription` | 9 |
| `GET /v1/subscriptions/{id}` | `Subscriptions::get($id)` | `$user->subscription()` | `Subscription` | 9 |
| `PATCH /v1/subscriptions/{id}` | `Subscriptions::update($id, ...)` | `$subscription->swap()`, `->resume()` | `Subscription` | 9 |
| `DELETE /v1/subscriptions/{id}` | `Subscriptions::cancel($id)` | `$subscription->cancel()` | `Subscription` | 9 |
| *(no create endpoint — via checkout)* | — | `$user->subscribeTo($productId)` → creates checkout | `CheckoutSession` | 9 |

## Webhooks — delivery (server side)

| Concern | Component | M |
| --- | --- | --- |
| Signature verification (`X-Bachs-Signature` = HMAC-SHA256 of `"{timestamp}.{raw_body}"`) | `Webhooks\SignatureVerifier` | 11 |
| Route `POST /bachs/webhook` + controller | `Http\Controllers\WebhookController` | 11 |
| Payload parsing + typed event resolution | `Webhooks\WebhookProcessor`, `Webhooks\WebhookEvent` | 11 |
| Typed Laravel events per Bachs event type | `Events\*` | 11 |
| Event persistence + dedupe on `evt_` id (optional) | `Models\BachsWebhookEvent` | 12 |
| Queued, retry-safe processing | config `queue` | 12 |
| Manual/queued delivery acknowledgement | controller 200-before-work | 12 |

## Webhooks — management API (Bachs API)

| Bachs endpoint | Resource | Laravel abstraction | DTO | M |
| --- | --- | --- | --- | --- |
| `GET/POST /v1/webhooks/endpoints` | `Webhooks::list()/create()` | `bachs:webhook:test` command | `WebhookEndpoint` | 15 |
| `GET/PATCH/DELETE /v1/webhooks/endpoints/{id}` | `Webhooks::get()/update()/delete()` | — | `WebhookEndpoint` | 15 |
| `GET /v1/webhooks/endpoints/{id}/events`, `GET .../{event_id}` | `Webhooks::events()` | `bachs:webhook:list` | `WebhookEvent` | 15 |
| `GET /v1/webhooks/endpoints/{id}/metrics` | `Webhooks::metrics()` | — | `WebhookMetrics` | 15 |
| `GET /v1/webhooks/endpoints/{id}/secret` | `Webhooks::secret()` | — | — | 15 |
| `POST .../events/{event_id}/resend` | `Webhooks::resend()` | `bachs:webhook:replay` | — | 15 |
| `POST .../rotate-secret` | `Webhooks::rotateSecret()` | — | — | 15 |
| `GET /v1/webhooks/events`, `GET /v1/webhooks/events/{id}` | `Webhooks::allEvents()/event()` | `bachs:webhook:inspect` | `WebhookEvent` | 15 |
| `POST /v1/webhooks/replay` | `Webhooks::replay()` | `bachs:webhook:replay` | — | 15 |

## Connect / marketplace (Phase 2)

| Bachs endpoint | Resource | DTO | M |
| --- | --- | --- | --- |
| `GET/POST /v1/organizations/connected-accounts`, `GET/PATCH /v1/connected-accounts/{id}` | `ConnectedAccounts::*` | `ConnectedAccount` | 16 |
| `GET .../capabilities`, `POST .../request-capabilities` | `ConnectedAccounts::capabilities()/requestCapability()` | `Capability` | 16 |
| `GET .../requirements/banks`, `.../momo`, `.../checklist`, `.../tasks`, `.../values` | `ConnectedAccounts::requirements*` | `TaskChecklist` | 16 |
| `POST .../requirements/submit` | `ConnectedAccounts::submitTasks()` | — | 16 |
| `POST .../requirements/accounts/resolve`, `POST .../payouts/resolve-account` | `ConnectedAccounts::resolveBankAccount()` | `ResolvedBankAccount` | 16 |
| `POST .../requirements/reusable-identity/apply`, `GET .../reusable-identity` | `ConnectedAccounts::applyReusableIdentity()` | — | 16 |
| `POST .../account-links` | `ConnectedAccounts::createAccountLink()` | `AccountLink` | 16 |
| `GET/POST .../uploads`, `GET .../uploads/{id}` | `ConnectedAccounts::uploadDocument()` | `Upload` | 16 |
| `GET/POST /v1/transfers`, `GET /v1/transfers/{id}` | `Transfers::*` | `Transfer` | 16 |
| Payouts/withdrawals endpoints | `Payouts::*` / `Withdrawals::*` | `Payout` | 16 |
| Disputes endpoints | `Disputes::*` | `Dispute` | 16 |
| Conversions endpoints | `Conversions::*` | `Conversion` | 16 |
| `GET/POST /v1/organizations/me` etc. | `Organizations::*` | `Organization` | 16 |
| Split payments (checkout `pricing`/Connect fields) | via `CheckoutSessions` | — | 16 |

---

## Webhook event → Laravel event mapping (M 11)

| Bachs event type | Laravel event |
| --- | --- |
| `checkout.completed` | `Events\CheckoutCompleted` |
| `checkout.expired` | `Events\CheckoutExpired` |
| `collection.succeeded` | `Events\PaymentSucceeded` |
| `collection.failed` | `Events\PaymentFailed` |
| `collection.underpaid` | `Events\PaymentUnderpaid` |
| `customer.subscription.created` | `Events\SubscriptionCreated` |
| `customer.subscription.updated` | `Events\SubscriptionUpdated` |
| `customer.subscription.deleted` | `Events\SubscriptionDeleted` |
| `invoice.created` | `Events\InvoiceCreated` |
| `invoice.paid` | `Events\InvoicePaid` |
| `invoice.payment_failed` | `Events\InvoicePaymentFailed` |
| `payout.created` | `Events\PayoutCreated` |
| `payout.paid` | `Events\PayoutPaid` |
| `payout.failed` | `Events\PayoutFailed` |
| `refund.created` | `Events\RefundCreated` |
| `refund.paid` | `Events\RefundPaid` |
| `refund.failed` | `Events\RefundFailed` |
| `dispute.created` | `Events\DisputeCreated` |
| `dispute.updated` | `Events\DisputeUpdated` |
| `conversion.completed` | `Events\ConversionCompleted` |
| `conversion.failed` | `Events\ConversionFailed` |
| `customer.created` | `Events\CustomerCreated` |
| `customer.updated` | `Events\CustomerUpdated` |
| `account.updated` | `Events\AccountUpdated` |
| `capability.updated` | `Events\CapabilityUpdated` |
| `transfer.created` | `Events\TransferCreated` |
| *(every inbound payload)* | `Events\WebhookReceived` (payload + verified state) |

---

## Out of scope for v1

- Direct subscription creation (Bachs has no endpoint).
- Anything not present in the current OpenAPI spec / docs (e.g. hypothetical endpoints). If docs are ambiguous, we flag rather than guess (see `research.md` §9).
- Client-side (JS) SDK — the package is server-side Laravel; `bachs.js` overlay usage is documented, not bundled.
