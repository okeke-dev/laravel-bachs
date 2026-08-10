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

_No stable API is offered yet. Everything above is subject to change while the
package is in early development (`0.x`)._
