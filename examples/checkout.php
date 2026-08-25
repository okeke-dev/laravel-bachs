<?php

/**
 * Checkout — create and manage checkout sessions.
 *
 * Bachs supports hosted checkout (redirect) and inline overlay (iframe).
 */

use OkekeDev\Bachs\Resources\CheckoutSessions;

// --- Create a hosted checkout session ---

$session = CheckoutSessions::create([
    'product_cart' => [
        ['product_id' => 'prod_xxx', 'quantity' => 1],
    ],
    'customer' => [
        'email' => 'bob@example.com',
    ],
    'success_url' => 'https://example.com/success?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => 'https://example.com/cancel',
]);

// Redirect the user to the hosted checkout page
return redirect($session->url());

// --- Attach to an existing customer ---

$session = CheckoutSessions::create([
    'product_cart' => [
        ['product_id' => 'prod_xxx', 'quantity' => 2],
    ],
    'customer' => [
        'customer_id' => 'cus_xxx',
    ],
    'success_url' => 'https://example.com/success',
]);

// --- Retrieve a session ---

$fetched = CheckoutSessions::get('checkout_xxx');
echo 'Status: '.$fetched->status().PHP_EOL;

// --- Using the Billable trait ---

$user = auth()->user();

// Quick checkout with email auto-populated
$session = $user->checkout([
    'product_cart' => [
        ['product_id' => 'prod_xxx', 'quantity' => 1],
    ],
]);

// Subscribe to a product (creates checkout for recurring product)
$session = $user->subscribeTo('prod_xxx');
