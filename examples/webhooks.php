<?php

/**
 * Webhooks — receive and process Bachs webhook events.
 *
 * Setup:
 *   1. Set BACHS_WEBHOOK_SECRET=whsec_xxx in .env
 *   2. Register the route (see below)
 *   3. Optionally enable database sync in config/bachs.php
 *
 * This file shows code snippets for each step. Copy the relevant
 * pieces into your own application files.
 */

// ---------------------------------------------------------------
// 1. Route registration (routes/web.php)
// ---------------------------------------------------------------
//
// use OkekeDev\Bachs\Http\Controllers\WebhookController;
//
// Route::post('bachs/webhook', [WebhookController::class, '__invoke']);

// ---------------------------------------------------------------
// 2. Event listeners (app/Providers/EventServiceProvider.php)
// ---------------------------------------------------------------
//
// use OkekeDev\Bachs\Events\PaymentSucceeded;
// use OkekeDev\Bachs\Events\PaymentFailed;
// use OkekeDev\Bachs\Events\SubscriptionCreated;
// use OkekeDev\Bachs\Events\SubscriptionCanceled;
// use OkekeDev\Bachs\Events\CheckoutCompleted;
//
// protected $listen = [
//     PaymentSucceeded::class => [
//         App\Listeners\SendPaymentConfirmation::class,
//     ],
//     PaymentFailed::class => [
//         App\Listeners\NotifyFailedPayment::class,
//     ],
//     SubscriptionCreated::class => [
//         App\Listeners\ActivateSubscription::class,
//     ],
//     SubscriptionCanceled::class => [
//         App\Listeners\HandleCancellation::class,
//     ],
//     CheckoutCompleted::class => [
//         App\Listeners\FulfillOrder::class,
//     ],
// ];

// ---------------------------------------------------------------
// 3. Listener example (app/Listeners/SendPaymentConfirmation.php)
// ---------------------------------------------------------------
//
// namespace App\Listeners;
//
// use OkekeDev\Bachs\Events\PaymentSucceeded;
//
// class SendPaymentConfirmation
// {
//     public function handle(PaymentSucceeded $event): void
//     {
//         $paymentId = $event->data['payment_id'] ?? 'unknown';
//
//         // Send confirmation email, update local records, etc.
//         logger("Payment succeeded: {$paymentId}");
//     }
// }

// ---------------------------------------------------------------
// 4. Enable local model sync (config/bachs.php)
// ---------------------------------------------------------------
//
// 'database' => [
//     'sync' => true,
// ],
//
// When sync is enabled, the package automatically upserts
// BachsCustomer, BachsProduct, BachsPayment, and BachsSubscription
// models on every relevant webhook event.

// ---------------------------------------------------------------
// 5. Artisan webhook commands
// ---------------------------------------------------------------
//
// php artisan bachs:webhook:test https://example.com/bachs/webhook
// php artisan bachs:webhook:list
// php artisan bachs:webhook:list --type=payment.succeeded
// php artisan bachs:webhook:inspect evt_xxx
// php artisan bachs:webhook:replay evt_xxx
