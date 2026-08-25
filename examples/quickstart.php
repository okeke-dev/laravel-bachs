<?php

/**
 * Quickstart — minimal setup to interact with the Bachs API.
 *
 * Prerequisites:
 *   1. composer require okeke-dev/laravel-bachs
 *   2. php artisan bachs:install
 *   3. Set BACHS_SECRET_KEY and BACHS_ENV in .env
 */

use OkekeDev\Bachs\Facades\Bachs;
use OkekeDev\Bachs\Resources\Balances;
use OkekeDev\Bachs\Resources\Currencies;
use OkekeDev\Bachs\Resources\Customers;
use OkekeDev\Bachs\Resources\Products;

// --- Facade / helper access ---

$client = Bachs::connection();          // default connection
$client = Bachs::connection('partner'); // named connection
$client = bachs();                     // global helper (same as default)

// --- List products ---

$products = Products::list(['limit' => 10]);

foreach ($products as $product) {
    echo $product->id().' — '.$product->name().PHP_EOL;
}

// --- Create a product ---

$product = Products::create([
    'name' => 'Starter Plan',
    'description' => 'Perfect for small projects',
    'price' => ['amount' => '9.99', 'currency' => 'USD'],
]);

echo 'Created: '.$product->id().PHP_EOL;

// --- Create a customer ---

$customer = Customers::create([
    'email' => 'alice@example.com',
    'name' => 'Alice Johnson',
]);

echo 'Customer: '.$customer->id().PHP_EOL;

// --- Supported currencies ---

$currencies = Currencies::supported();

// --- Account balance ---

$balance = Balances::get();
echo 'Available: '.$balance->available().PHP_EOL;

// --- Low-level transport ---

$response = $client->get('products', ['limit' => 5]);
echo 'Status: '.$response->status().PHP_EOL;
echo 'Request ID: '.$response->requestId().PHP_EOL;
