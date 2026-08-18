<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Dto\Payment;
use OkekeDev\Bachs\Dto\Refund;
use OkekeDev\Bachs\Resources\Payments;
use OkekeDev\Bachs\Resources\Refunds;

it('lists payments as a paginated collection', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/payments*' => Http::response([
            'items' => [
                ['payment_id' => 'pay_1', 'status' => 'succeeded', 'amount' => '29.00', 'currency' => 'USD'],
                ['payment_id' => 'pay_2', 'status' => 'processing', 'amount' => '49.00', 'currency' => 'USD'],
            ],
            'pagination' => [
                'has_more' => false,
                'next_cursor' => null,
                'prev_cursor' => null,
                'limit' => 20,
                'offset' => 0,
                'returned' => 2,
                'total' => 2,
            ],
        ]),
    ]);

    $payments = Payments::list();

    expect($payments)->toBeInstanceOf(PaginatedCollection::class)
        ->and($payments->count())->toBe(2)
        ->and($payments->first())->toBeInstanceOf(Payment::class)
        ->and($payments->first()->id())->toBe('pay_1')
        ->and($payments->first()->isSucceeded())->toBeTrue();
});

it('fetches a payment', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/payments/pay_1' => Http::response([
            'payment_id' => 'pay_1',
            'charge_id' => 'chr_1',
            'reference' => 'ref_abc',
            'billing_reason' => 'purchase',
            'checkout_id' => 'chk_1',
            'status' => 'succeeded',
            'is_refundable' => true,
            'amount' => '29.00',
            'amount_paid' => '29.00',
            'amount_remaining' => '0.00',
            'currency' => 'USD',
            'fee_usd' => '1.50',
            'payment_method' => 'card',
            'subscription_id' => 'sub_1',
            'line_items' => [['product_id' => 'prod_1', 'name' => 'Pro Plan']],
            'refunds' => [],
            'status_history' => [['status' => 'created', 'timestamp' => '2026-01-15T10:00:00Z']],
            'created_at' => '2026-01-15T10:00:00Z',
        ]),
    ]);

    $payment = Payments::get('pay_1');

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->id())->toBe('pay_1')
        ->and($payment->chargeId())->toBe('chr_1')
        ->and($payment->reference())->toBe('ref_abc')
        ->and($payment->billingReason())->toBe('purchase')
        ->and($payment->checkoutId())->toBe('chk_1')
        ->and($payment->status())->toBe('succeeded')
        ->and($payment->isSucceeded())->toBeTrue()
        ->and($payment->isRefundable())->toBeTrue()
        ->and($payment->amount())->toBe('29.00')
        ->and($payment->amountPaid())->toBe('29.00')
        ->and($payment->amountRemaining())->toBe('0.00')
        ->and($payment->currency())->toBe('USD')
        ->and($payment->feeUsd())->toBe('1.50')
        ->and($payment->paymentMethod())->toBe('card')
        ->and($payment->subscriptionId())->toBe('sub_1')
        ->and($payment->lineItems())->toHaveCount(1)
        ->and($payment->refunds())->toBe([])
        ->and($payment->statusHistory())->toHaveCount(1);
});

it('fetches a payment by charge id', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/payments/charges/chr_1' => Http::response([
            'payment_id' => 'pay_1',
            'charge_id' => 'chr_1',
            'status' => 'succeeded',
            'amount' => '29.00',
            'currency' => 'USD',
        ]),
    ]);

    $payment = Payments::getByCharge('chr_1');

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->chargeId())->toBe('chr_1');

    Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.bachs.io/v1/payments/charges/chr_1');
});

it('identifies payment statuses correctly', function () {
    $succeeded = Payment::from(['payment_id' => 'pay_1', 'status' => 'succeeded']);
    $processing = Payment::from(['payment_id' => 'pay_2', 'status' => 'processing']);
    $failed = Payment::from(['payment_id' => 'pay_3', 'status' => 'failed']);
    $refunded = Payment::from(['payment_id' => 'pay_4', 'status' => 'refunded']);
    $partiallyRefunded = Payment::from(['payment_id' => 'pay_5', 'status' => 'partially_refunded']);
    $expired = Payment::from(['payment_id' => 'pay_6', 'status' => 'expired']);
    $cancelled = Payment::from(['payment_id' => 'pay_7', 'status' => 'cancelled']);

    expect($succeeded->isSucceeded())->toBeTrue()
        ->and($processing->isProcessing())->toBeTrue()
        ->and($failed->isFailed())->toBeTrue()
        ->and($refunded->isRefunded())->toBeTrue()
        ->and($partiallyRefunded->isPartiallyRefunded())->toBeTrue()
        ->and($expired->isExpired())->toBeTrue()
        ->and($cancelled->isCancelled())->toBeTrue();
});

it('creates a refund', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/refunds' => Http::response([
            'refund_id' => 'ref_1',
            'charge_id' => 'chr_1',
            'status' => 'processing',
            'requested_amount' => '29.00',
            'reason' => 'customer_request',
            'created_at' => '2026-01-15T10:00:00Z',
        ], 201),
    ]);

    $refund = Refunds::create([
        'charge_id' => 'chr_1',
        'amount' => '29.00',
        'reason' => 'customer_request',
    ]);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/refunds'
            && $request['charge_id'] === 'chr_1'
            && $request['amount'] === '29.00';
    });

    expect($refund)->toBeInstanceOf(Refund::class)
        ->and($refund->id())->toBe('ref_1')
        ->and($refund->chargeId())->toBe('chr_1')
        ->and($refund->status())->toBe('processing')
        ->and($refund->isProcessing())->toBeTrue()
        ->and($refund->requestedAmount())->toBe('29.00')
        ->and($refund->reason())->toBe('customer_request');
});

it('lists refunds as a paginated collection', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/refunds*' => Http::response([
            'items' => [
                ['refund_id' => 'ref_1', 'status' => 'success', 'charge_id' => 'chr_1'],
                ['refund_id' => 'ref_2', 'status' => 'processing', 'charge_id' => 'chr_2'],
            ],
            'pagination' => [
                'has_more' => false,
                'next_cursor' => null,
                'prev_cursor' => null,
                'limit' => 20,
                'offset' => 0,
                'returned' => 2,
                'total' => 2,
            ],
        ]),
    ]);

    $refunds = Refunds::list();

    expect($refunds)->toBeInstanceOf(PaginatedCollection::class)
        ->and($refunds->count())->toBe(2)
        ->and($refunds->first())->toBeInstanceOf(Refund::class)
        ->and($refunds->first()->id())->toBe('ref_1')
        ->and($refunds->first()->isSuccess())->toBeTrue();
});

it('fetches a refund', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/refunds/ref_1' => Http::response([
            'refund_id' => 'ref_1',
            'charge_id' => 'chr_1',
            'status' => 'success',
            'requested_amount' => '29.00',
            'refunded_amount' => '29.00',
            'refund_fee_amount' => '1.50',
            'fee_bearer' => 'org',
            'reason' => 'customer_request',
            'created_at' => '2026-01-15T10:00:00Z',
        ]),
    ]);

    $refund = Refunds::get('ref_1');

    expect($refund)->toBeInstanceOf(Refund::class)
        ->and($refund->id())->toBe('ref_1')
        ->and($refund->chargeId())->toBe('chr_1')
        ->and($refund->isSuccess())->toBeTrue()
        ->and($refund->requestedAmount())->toBe('29.00')
        ->and($refund->refundedAmount())->toBe('29.00')
        ->and($refund->refundFeeAmount())->toBe('1.50')
        ->and($refund->feeBearer())->toBe('org')
        ->and($refund->reason())->toBe('customer_request');
});

it('fetches refunds by charge id', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/refunds/by-charge/chr_1*' => Http::response([
            'items' => [
                ['refund_id' => 'ref_1', 'charge_id' => 'chr_1', 'status' => 'success'],
            ],
            'pagination' => [
                'has_more' => false,
                'next_cursor' => null,
                'prev_cursor' => null,
                'limit' => 20,
                'offset' => 0,
                'returned' => 1,
                'total' => 1,
            ],
        ]),
    ]);

    $refunds = Refunds::getByCharge('chr_1');

    expect($refunds)->toBeInstanceOf(PaginatedCollection::class)
        ->and($refunds->first()->chargeId())->toBe('chr_1');

    Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.bachs.io/v1/refunds/by-charge/chr_1');
});

it('identifies refund statuses correctly', function () {
    $processing = Refund::from(['refund_id' => 'ref_1', 'status' => 'processing']);
    $success = Refund::from(['refund_id' => 'ref_2', 'status' => 'success']);
    $failed = Refund::from(['refund_id' => 'ref_3', 'status' => 'failed']);

    expect($processing->isProcessing())->toBeTrue()
        ->and($success->isSuccess())->toBeTrue()
        ->and($failed->isFailed())->toBeTrue();
});

it('creates a refund from a payment instance', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/payments/pay_1' => Http::response([
            'payment_id' => 'pay_1',
            'charge_id' => 'chr_1',
            'status' => 'succeeded',
            'is_refundable' => true,
        ]),
        'sandbox-api.bachs.io/v1/refunds' => Http::response([
            'refund_id' => 'ref_1',
            'charge_id' => 'chr_1',
            'status' => 'processing',
            'requested_amount' => '29.00',
        ], 201),
    ]);

    $payment = Payments::get('pay_1');
    $refund = $payment->refund(['amount' => '29.00', 'reason' => 'duplicate']);

    expect($refund)->toBeInstanceOf(Refund::class)
        ->and($refund->chargeId())->toBe('chr_1');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['charge_id'] === 'chr_1'
            && $request['amount'] === '29.00'
            && $request['reason'] === 'duplicate';
    });
});
