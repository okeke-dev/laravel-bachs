<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default connection
    |--------------------------------------------------------------------------
    |
    | The name of the connection used when none is passed explicitly, e.g.
    | `Bachs::connection()`.
    |
    */

    'default' => env('BACHS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | Each connection maps to a Bachs organization / API key. The "default"
    | connection is the common case; additional connections let you talk to
    | several Bachs accounts from one application.
    |
    | Secret keys are read from the environment, never hardcoded.
    |
    */

    'connections' => [

        'default' => [

            /*
            | The Bachs secret key (`sk_sandbox_...` or `sk_live_...`).
            | Find it in the Developer section of your Bachs dashboard.
            */

            'secret' => env('BACHS_SECRET_KEY'),

            /*
            | Target environment: `sandbox` or `live`.
            |
            | The base URL is derived from this value (and the key prefix as a
            | safety check) unless an explicit `base_url` is set below.
            */

            'env' => env('BACHS_ENV', 'sandbox'),

            /*
            | Explicit API base URL override. When set, this wins over `env`.
            | Leave null to use the sandbox/live URLs automatically.
            */

            'base_url' => env('BACHS_BASE_URL'),

            /*
            | API version segment appended to the sandbox/live host, e.g. `v1`.
            | Ignored when an explicit `base_url` is set.
            */

            'api_version' => env('BACHS_API_VERSION', 'v1'),

            /*
            | Default currency used when a request does not specify one.
            */

            'currency' => env('BACHS_CURRENCY', 'USD'),

            /*
            | Additional headers sent with every request on this connection,
            | e.g. a per-account identifier. Transport-reserved headers
            | (Authorization, Accept, Content-Type, Idempotency-Key) cannot
            | be overridden here.
            */

            'headers' => [
                // 'X-Account' => env('BACHS_ACCOUNT_ID'),
            ],

            /*
            | HTTP request timeout, in seconds.
            */

            'timeout' => (int) env('BACHS_TIMEOUT', 30),

            /*
            | TCP connect timeout, in seconds.
            */

            'connect_timeout' => (int) env('BACHS_CONNECT_TIMEOUT', 10),

            /*
            | Retry policy for transient failures (429, 5xx, network errors).
            | `times` is the number of retries (additional attempts beyond the
            | first). Mutating requests are only auto-retried when an idempotency
            | key is present (see docs/architecture.md).
            |
            | The wait between attempts grows exponentially: the first retry
            | waits `sleep_ms`, doubling by `multiplier` up to `max_sleep_ms`.
            | A 429 response's `Retry-After` header always takes precedence.
            */

            'retry' => [
                'times' => (int) env('BACHS_RETRY_TIMES', 2),
                'sleep_ms' => (int) env('BACHS_RETRY_SLEEP_MS', 100),
                'multiplier' => (float) env('BACHS_RETRY_MULTIPLIER', 2.0),
                'max_sleep_ms' => (int) env('BACHS_RETRY_MAX_SLEEP_MS', 5000),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */

    'webhook' => [

        /*
        | The signing secret for your webhook endpoint (`whsec_...`).
        | Used to verify the `X-Bachs-Signature` header on deliveries.
        */

        'secret' => env('BACHS_WEBHOOK_SECRET'),

        /*
        | Route path where Bachs delivers events.
        */

        'path' => env('BACHS_WEBHOOK_PATH', 'bachs/webhook'),

        /*
        | Queue connection used for processing webhooks. `null` processes
        | synchronously after acknowledging the delivery.
        */

        'queue' => env('BACHS_WEBHOOK_QUEUE'),

        /*
        | Queue name for webhook processing jobs. When null, the default
        | queue for the chosen connection is used.
        */

        'queue_name' => env('BACHS_WEBHOOK_QUEUE_NAME'),

        /*
        | Maximum age (seconds) of a signed delivery before it is rejected
        | as stale / a replay.
        */

        'tolerance' => (int) env('BACHS_WEBHOOK_TOLERANCE', 300),

        /*
        | Extra middleware applied to the webhook route.
        */

        'middleware' => [],

    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Safe request metadata (method, path, status, duration, request id, event
    | id) is logged when enabled. Secrets and payment-sensitive data are never
    | logged.
    |
    */

    'logging' => [
        'enabled' => (bool) env('BACHS_LOGGING_ENABLED', true),
        'channel' => env('BACHS_LOG_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database synchronization
    |--------------------------------------------------------------------------
    |
    | Optional local mirrors of Bachs resources, kept in sync by webhooks.
    | Bachs remains the source of truth; local rows never drive billing.
    |
    */

    'database' => [
        'sync' => (bool) env('BACHS_DB_SYNC', false),
        'connection' => env('BACHS_DB_CONNECTION'),
        'tables' => [
            'customers' => 'bachs_customers',
            'products' => 'bachs_products',
            'payments' => 'bachs_payments',
            'subscriptions' => 'bachs_subscriptions',
            'webhook_events' => 'bachs_webhook_events',
        ],
    ],

];
