<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Dto\CheckoutSession;
use OkekeDev\Bachs\Dto\Customer;
use OkekeDev\Bachs\Dto\Subscription;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Tests\Fixtures\BillableUser;

beforeEach(function () {
    $this->user = new BillableUser;
    $this->user->id = 1;
    $this->user->email = 'jane@example.com';
    $this->user->name = 'Jane Doe';
});

it('provides the customer ID column name', function () {
    expect(BillableUser::getBachsCustomerIdColumn())->toBe('bachs_customer_id');
});

it('detects when a model has no bachs customer', function () {
    expect($this->user->hasBachsCustomer())->toBeFalse()
        ->and($this->user->bachsCustomerId())->toBeNull();
});

it('detects when a model has a bachs customer', function () {
    $this->user->bachs_customer_id = 'cust_1';

    expect($this->user->hasBachsCustomer())->toBeTrue()
        ->and($this->user->bachsCustomerId())->toBe('cust_1');
});

it('creates a bachs customer and stores the id', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers' => Http::response([
            'customer_id' => 'cust_new',
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'created_at' => '2026-01-15T10:00:00Z',
            'updated_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $customer = $this->user->createAsBachsCustomer();

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->id())->toBe('cust_new')
        ->and($this->user->bachsCustomerId())->toBe('cust_new')
        ->and($this->user->hasBachsCustomer())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/customers'
            && $request['email'] === 'jane@example.com'
            && $request['name'] === 'Jane Doe';
    });
});

it('creates a bachs customer with custom params', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers' => Http::response([
            'customer_id' => 'cust_custom',
            'email' => 'custom@example.com',
            'name' => 'Custom User',
        ], 201),
    ]);

    $customer = $this->user->createAsBachsCustomer([
        'email' => 'custom@example.com',
        'name' => 'Custom User',
        'phone_number' => '+2348012345678',
    ]);

    expect($customer->id())->toBe('cust_custom');

    Http::assertSent(function ($request) {
        return $request['email'] === 'custom@example.com'
            && $request['name'] === 'Custom User'
            && $request['phone_number'] === '+2348012345678';
    });
});

it('throws when creating a customer that already exists', function () {
    $this->user->bachs_customer_id = 'cust_existing';

    $this->user->createAsBachsCustomer();
})->throws(BachsInvalidArgumentException::class, 'already has a Bachs customer');

it('retrieves the bachs customer from the api', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers/cust_1' => Http::response([
            'customer_id' => 'cust_1',
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]),
    ]);

    $this->user->bachs_customer_id = 'cust_1';

    $customer = $this->user->bachsCustomer();

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->id())->toBe('cust_1')
        ->and($customer->email())->toBe('jane@example.com');

    Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.bachs.io/v1/customers/cust_1');
});

it('returns null when retrieving customer for model without one', function () {
    expect($this->user->bachsCustomer())->toBeNull();
});

it('updates the bachs customer', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers/cust_1' => Http::response([
            'customer_id' => 'cust_1',
            'email' => 'jane@example.com',
            'name' => 'Jane Smith',
            'updated_at' => '2026-01-16T10:00:00Z',
        ]),
    ]);

    $this->user->bachs_customer_id = 'cust_1';

    $customer = $this->user->updateBachsCustomer(['name' => 'Jane Smith']);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->name())->toBe('Jane Smith');

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/customers/cust_1'
            && $request['name'] === 'Jane Smith';
    });
});

it('throws when updating customer that does not exist', function () {
    $this->user->updateBachsCustomer(['name' => 'Test']);
})->throws(BachsInvalidArgumentException::class, 'does not have a Bachs customer');

it('creates a portal session and returns the url', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/customers/cust_1/portal-sessions' => Http::response([
            'session_id' => 'ps_1',
            'customer_id' => 'cust_1',
            'url' => 'https://portal.bachs.io/session/ps_1',
            'status' => 'active',
            'created_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $this->user->bachs_customer_id = 'cust_1';

    $url = $this->user->billingPortalUrl();

    expect($url)->toBe('https://portal.bachs.io/session/ps_1');

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/customers/cust_1/portal-sessions');
});

it('throws when creating portal session for model without customer', function () {
    $this->user->billingPortalUrl();
})->throws(BachsInvalidArgumentException::class, 'does not have a Bachs customer');

it('creates a checkout session with existing customer', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/checkout-sessions' => Http::response([
            'checkout_id' => 'chk_1',
            'checkout_url' => 'https://checkout.bachs.io/chk_1',
            'status' => 'OPEN',
            'created_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $this->user->bachs_customer_id = 'cust_1';

    $session = $this->user->checkout([
        'product_cart' => [['product_id' => 'prod_1', 'quantity' => 1]],
        'success_url' => 'https://example.com/success',
    ]);

    expect($session)->toBeInstanceOf(CheckoutSession::class)
        ->and($session->id())->toBe('chk_1')
        ->and($session->url())->toBe('https://checkout.bachs.io/chk_1');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/checkout-sessions'
            && $request['customer']['customer_id'] === 'cust_1';
    });
});

it('creates a checkout session without existing customer', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/checkout-sessions' => Http::response([
            'checkout_id' => 'chk_2',
            'checkout_url' => 'https://checkout.bachs.io/chk_2',
            'status' => 'OPEN',
            'created_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $session = $this->user->checkout([
        'product_cart' => [['product_id' => 'prod_1', 'quantity' => 1]],
        'success_url' => 'https://example.com/success',
    ]);

    expect($session)->toBeInstanceOf(CheckoutSession::class)
        ->and($session->id())->toBe('chk_2');

    Http::assertSent(function ($request) {
        return $request['customer']['email'] === 'jane@example.com'
            && $request['customer']['name'] === 'Jane Doe';
    });
});

it('subscribes to a product via checkout', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/checkout-sessions' => Http::response([
            'checkout_id' => 'chk_sub',
            'checkout_url' => 'https://checkout.bachs.io/chk_sub',
            'status' => 'OPEN',
            'created_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $this->user->bachs_customer_id = 'cust_1';

    $session = $this->user->subscribeTo('prod_recurring');

    expect($session)->toBeInstanceOf(CheckoutSession::class)
        ->and($session->id())->toBe('chk_sub');

    Http::assertSent(function ($request) {
        return $request['customer']['customer_id'] === 'cust_1'
            && $request['product_cart'][0]['product_id'] === 'prod_recurring';
    });
});

it('returns null subscription when no subscription id stored', function () {
    expect($this->user->subscription())->toBeNull();
});

it('fetches the active subscription', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/subscriptions/sub_1' => Http::response([
            'id' => 'sub_1',
            'customer' => ['customer_id' => 'cust_1'],
            'status' => 'active',
            'amount' => '29.00',
            'currency' => 'USD',
            'product' => ['id' => 'prod_1'],
        ]),
    ]);

    $this->user->bachs_subscription_id = 'sub_1';

    $subscription = $this->user->subscription();

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->id())->toBe('sub_1')
        ->and($subscription->isActive())->toBeTrue();
});

it('returns true for subscribed when subscription is active', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/subscriptions/sub_1' => Http::response([
            'id' => 'sub_1',
            'status' => 'active',
        ]),
    ]);

    $this->user->bachs_subscription_id = 'sub_1';

    expect($this->user->subscribed())->toBeTrue();
});

it('returns false for subscribed when no subscription', function () {
    expect($this->user->subscribed())->toBeFalse();
});

it('cancels the active subscription', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/subscriptions/sub_1' => Http::response([
            'id' => 'sub_1',
            'status' => 'canceled',
            'canceled_at' => '2026-01-15T10:00:00Z',
        ]),
    ]);

    $this->user->bachs_subscription_id = 'sub_1';

    $subscription = $this->user->cancel();

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->isCanceled())->toBeTrue();

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/subscriptions/sub_1');
});

it('throws when canceling without subscription', function () {
    $this->user->cancel();
})->throws(BachsInvalidArgumentException::class, 'does not have an active subscription');

it('resumes a canceled subscription', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/subscriptions/sub_1' => Http::response([
            'id' => 'sub_1',
            'status' => 'active',
            'cancel_at_period_end' => false,
        ]),
    ]);

    $this->user->bachs_subscription_id = 'sub_1';

    $subscription = $this->user->resume();

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->cancelAtPeriodEnd())->toBeFalse();

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/subscriptions/sub_1'
            && $request['cancel_at_period_end'] === false;
    });
});

it('throws when resuming without subscription', function () {
    $this->user->resume();
})->throws(BachsInvalidArgumentException::class, 'does not have a subscription to resume');
