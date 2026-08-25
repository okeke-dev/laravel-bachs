<?php

/**
 * Billable trait — attach billing to any Eloquent model.
 *
 * This example shows how to make your User model billable via Bachs.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OkekeDev\Bachs\Concerns\Billsable;

class User extends Authenticatable
{
    use Billsable, HasFactory;

    // Optional: custom column names (defaults shown)
    // protected static string $bachsCustomerIdColumn = 'bachs_customer_id';
    // protected static string $bachsSubscriptionIdColumn = 'bachs_subscription_id';
}

// ---------------------------------------------------------------
// Usage examples (place in your controllers / actions)
// ---------------------------------------------------------------
//
// // 1. Create a Bachs customer from the authenticated user
// $user = auth()->user();
// $customer = $user->createAsBachsCustomer();
// echo 'Bachs customer ID: ' . $customer->id();
//
// // 2. Check if the user already has a customer
// if ($user->hasBachsCustomer()) {
//     echo 'Customer exists: ' . $user->bachsCustomerId();
// }
//
// // 3. Start a checkout session
// $session = $user->checkout([
//     'product_cart' => [['product_id' => 'prod_xxx', 'quantity' => 1]],
//     'success_url' => route('checkout.success'),
//     'cancel_url' => route('checkout.cancel'),
// ]);
// return redirect($session->url());
//
// // 4. Subscribe to a product
// $session = $user->subscribeTo('prod_xxx', [
//     'success_url' => route('subscription.success'),
// ]);
// return redirect($session->url());
//
// // 5. Check subscription status
// if ($user->subscribed()) {
//     echo 'User has an active subscription';
// }
// $subscription = $user->subscription();
// if ($subscription?->isActive()) {
//     echo 'Renews at: ' . $subscription->currentPeriodEnd();
// }
//
// // 6. Cancel / resume
// $user->cancel();
// $user->resume();
//
// // 7. Billing portal
// return redirect($user->billingPortalUrl());
//
// // 8. Update the Bachs customer
// $user->updateBachsCustomer(['name' => 'Jane Smith']);
