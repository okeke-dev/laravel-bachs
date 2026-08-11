# Roadmap

> Milestone plan for **laravel-bachs**. Each milestone follows the same checklist:
> research → explain → implement → tests → run tests → static analysis → format → review diff → docs → risks.
> Status legend: ⬜ pending · 🔄 in progress · ✅ done.

---

## Milestones

| # | Name | Focus | Status |
| --- | --- | --- | --- |
| 0 | Research & architecture | Docs: research, api-coverage, architecture, roadmap, design-decisions | ✅ docs drafted |
| 1 | Repository & package foundation | composer.json, PHPUnit/Pest + Testbench, Pint, PHPStan/Larastan, CI matrix, skeleton service provider, config, LICENSE, CONTRIBUTING, SECURITY, CHANGELOG | ✅ |
| 2 | HTTP client & authentication | `BachsClient`, `BachsRequest`, auth header, base-URL selection, timeout/retry/backoff, rate-limit headers, safe logging, `x-request-id` | ✅ |
| 3 | Core resource API | Products, product groups (pass-through), payment methods/rails, currencies, media/uploads, balances — Tier A passthrough + `PaginatedCollection` | ✅ |
| 4 | DTOs, exceptions, value objects | All DTOs, `Money`, `Currency`, exception hierarchy + mapping, `PaginatedCollection` | ✅ |
| 5 | Laravel container | `BachsServiceProvider`, `BachsManager`, `Bachs` facade, helper, multi-connection, config publishing | ⬜ |
| 6 | Customers | Customers resource + portal sessions, customer DTO, auto-sync, `$user->createAsBachsCustomer()` | ⬜ |
| 7 | Billable | `Concerns\Billable`: customer mapping, subscription helpers, checkout/portal/refund shortcuts | ⬜ |
| 8 | Checkout | CheckoutSessions resource, `$user->checkout()`, `CheckoutSession::redirect()`, overlay docs, Inertia/Livewire notes | ⬜ |
| 9 | Subscriptions | Subscriptions resource, status helpers, swap/resume/cancel semantics, portal URL, `$user->subscribeTo()` | ⬜ |
| 10 | Payments & refunds | Payments, refunds, payment methods; `refund()` helper; webhook confirmation guidance | ⬜ |
| 11 | Webhooks (delivery) | SignatureVerifier, WebhookProcessor, WebhookEvent, typed events, `WebhookController`, configurable route/middleware | ⬜ |
| 12 | Webhook persistence/idempotency/queues | `bachs_webhook_events` store, `evt_` dedupe, queue + retry-safe processing, ack-before-work | ⬜ |
| 13 | Local synchronized models | BachsCustomer/Product/Payment/Subscription models, migrations, webhook sync (opt-in) | ⬜ |
| 14 | Blade integration | `x-bachs-checkout` components (hosted + overlay + subscribe variants), accessible, unstyled | ⬜ |
| 15 | Artisan tooling | `bachs:install`, `bachs:health`, `bachs:webhook:test`, `bachs:webhook:list`, `bachs:webhook:inspect`, `bachs:webhook:replay` | ⬜ |
| 16 | Marketplace functionality | Connected accounts, capabilities, tasks/checklist/requirements, account links, uploads, transfers, payouts/withdrawals, disputes, conversions, organizations, split payments (Tier A) | ⬜ |
| 17 | Testing hardening | Coverage sweep, regression tests from bugs, fuzz-ish payload tests, rate-limit/retry tests, matrix CI hardening | ⬜ |
| 18 | Documentation & OSS polish | Full docs set, README, badges, changelog, issue/PR templates, examples | ⬜ |
| 19 | Release candidate | Semver tagging, publish checklist, Packagist metadata, final audit (secrets, security) | ⬜ |

---

## Dependency order

- 1 → 2 → 3/4 (parallel-ish; 4 unblocks 3's DTO returns) → 5 → 6 → 7 → 8 → 9 → 10 → 11 → 12 → 13 → 14 → 15 → 17 → 18 → 19.
- Marketplace (16) is intentionally last and independent; the Tier-A SDK pattern set in milestones 2–4 makes it additive rather than disruptive.

---

## Phase plan for v1.0 scope

**v1.0 (this package)** — milestones 1–15 + 17–19. Anything in milestone 16 ships as **v1.x** after 1.0 is stable.

**v1.0 goals (cut lines)**
- Full Tier-A SDK for core billing resources (customers, products, checkout, payments, refunds, subscriptions, balances, currencies, payment methods, media).
- Billable + customer sync + checkout helpers.
- Webhook delivery with signature verification, typed events, optional persistence/dedupe/queues.
- Local models opt-in.
- Blade components (hosted/overlay/subscribe).
- Artisan tooling.
- CI on PHP 8.2/8.3 (expand after verifying ecosystem) × Laravel 11/12.
- Complete docs + OSS polish.

**Not in v1.0**
- Marketplace/Connect/Payouts/Disputes/Conversions (milestone 16) — documented as v1.x.
- Any unverified API capability — the spec is the contract.
- Client-side JS bundle.

---

## Acceptance criteria per milestone

1. Feature is implemented behind the documented public API.
2. Unit + feature tests cover happy path and error paths (HTTP fakes, webhook fixtures).
3. `pint --test`, `phpstan` (level target per milestone), and full test suite pass locally and in CI.
4. Public API is semver-stable; breaking changes documented in CHANGELOG.
5. Docs updated for the milestone's features.
