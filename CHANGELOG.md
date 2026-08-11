# Changelog

All notable changes to **laravel-bachs** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

### Added

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
