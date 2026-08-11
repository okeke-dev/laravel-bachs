<?php

use OkekeDev\Bachs\Dto\Balance;
use OkekeDev\Bachs\Dto\BalanceBucket;
use OkekeDev\Bachs\Dto\MediaItem;
use OkekeDev\Bachs\Dto\PaymentMethod;
use OkekeDev\Bachs\Dto\PaymentRailLookup;
use OkekeDev\Bachs\Dto\PaymentRailOption;
use OkekeDev\Bachs\Dto\Product;
use OkekeDev\Bachs\Dto\ProductGroup;
use OkekeDev\Bachs\Dto\SupportedCurrencies;
use OkekeDev\Bachs\Dto\Upload;
use OkekeDev\Bachs\ValueObjects\Money;

it('keeps the raw payload reachable for forward compatibility', function () {
    $product = Product::from(['id' => 'prod_1', 'name' => 'T-shirt', 'future_field' => 'x']);

    expect($product->raw())->toBe(['id' => 'prod_1', 'name' => 'T-shirt', 'future_field' => 'x'])
        ->and($product->toArray())->toBe($product->raw());
});

it('hydrates a product with typed accessors', function () {
    $product = Product::from([
        'id' => 'prod_1',
        'organization_id' => 'org_1',
        'name' => 'Pro Plan',
        'description' => 'Monthly access',
        'price' => ['currency' => 'USD', 'price_type' => 'fixed', 'amount' => '29.00'],
        'status' => 'active',
        'metadata' => ['tier' => 'pro'],
        'media' => [['id' => 'upl_1', 'url' => 'https://cdn.example.com/x.png', 'file_name' => 'x.png', 'mime_type' => 'image/png', 'file_size_bytes' => 2048]],
        'actor_id' => 'usr_1',
        'created_at' => '2026-07-13T14:00:00.000Z',
        'updated_at' => '2026-07-13T14:00:00.000Z',
        'billing_cycle' => ['interval' => 'month', 'frequency' => 1],
        'trial_period' => ['interval' => 'day', 'frequency' => 14],
        'prices' => [['currency' => 'USD', 'amount' => '29.00']],
        'total_payments' => 4,
        'total_amount' => '116.00',
    ]);

    expect($product->id())->toBe('prod_1')
        ->and($product->organizationId())->toBe('org_1')
        ->and($product->name())->toBe('Pro Plan')
        ->and($product->description())->toBe('Monthly access')
        ->and($product->isActive())->toBeTrue()
        ->and($product->isArchived())->toBeFalse()
        ->and($product->isRecurring())->toBeTrue()
        ->and($product->metadata())->toBe(['tier' => 'pro'])
        ->and($product->media())->toHaveCount(1)
        ->and($product->media()[0])->toBeInstanceOf(MediaItem::class)
        ->and($product->price()->amount()->amount())->toBe('29.00')
        ->and($product->billingCycle()->interval())->toBe('month')
        ->and($product->trialPeriod()->frequency())->toBe(14)
        ->and($product->totalPayments())->toBe(4)
        ->and($product->totalAmount()->amount())->toBe('116.00')
        ->and($product->actorId())->toBe('usr_1');
});

it('exposes price helpers and money fields', function () {
    $product = Product::from([
        'id' => 'prod_2',
        'name' => 'Donations',
        'price' => ['currency' => 'NGN', 'price_type' => 'custom', 'preset_amount' => '1000.00', 'minimum_amount' => '100.00', 'maximum_amount' => '5000.00'],
    ]);

    $price = $product->price();

    expect($price->isCustom())->toBeTrue()
        ->and($price->isFixed())->toBeFalse()
        ->and($price->isFree())->toBeFalse()
        ->and($price->presetAmount()->amount())->toBe('1000.00')
        ->and($price->minimumAmount()->amount())->toBe('100.00')
        ->and($price->maximumAmount()->amount())->toBe('5000.00')
        ->and($price->currency()->code())->toBe('NGN')
        ->and($price->currencyOptions())->toBe([]);
});

it('treats absent product fields defensively', function () {
    $product = Product::from(['id' => 'prod_3', 'name' => 'Minimal']);

    expect($product->description())->toBeNull()
        ->and($product->metadata())->toBeNull()
        ->and($product->media())->toBe([])
        ->and($product->billingCycle())->toBeNull()
        ->and($product->trialPeriod())->toBeNull()
        ->and($product->totalAmount())->toBeNull()
        ->and($product->isRecurring())->toBeFalse();
});

it('hydrates a product group with nested products', function () {
    $group = ProductGroup::from([
        'id' => 'pgrp_1',
        'organization_id' => 'org_1',
        'name' => 'Merch',
        'products' => [
            ['id' => 'prod_1', 'name' => 'T-shirt'],
            ['id' => 'prod_2', 'name' => 'Hoodie'],
        ],
    ]);

    expect($group->name())->toBe('Merch')
        ->and($group->productCount())->toBe(2)
        ->and($group->products()[0])->toBeInstanceOf(Product::class)
        ->and($group->products()[1]->name())->toBe('Hoodie');
});

it('hydrates payment methods and rails', function () {
    $method = PaymentMethod::from([
        'id' => 'card',
        'display_name' => 'Debit/Credit Card',
        'icon' => 'card',
        'description' => 'Pay with Visa, Mastercard, or Verve',
        'type' => 'fiat',
        'enabled_by_default' => true,
        'currencies' => ['USD', 'NGN', 'GHS'],
    ]);

    expect($method->id())->toBe('card')
        ->and($method->displayName())->toBe('Debit/Credit Card')
        ->and($method->isFiat())->toBeTrue()
        ->and($method->isCrypto())->toBeFalse()
        ->and($method->enabledByDefault())->toBeTrue()
        ->and($method->currencies())->toHaveCount(3)
        ->and($method->supportsCurrency('NGN'))->toBeTrue()
        ->and($method->supportsCurrency('KES'))->toBeFalse();

    $lookup = PaymentRailLookup::from([
        'payment_method' => 'bank_transfer',
        'currency' => 'NGN',
        'country_code' => 'NG',
        'rails' => [
            ['id' => 'bank_transfer_ng', 'name' => 'Bank Transfer Nigeria', 'active' => true],
            ['id' => 'bank_transfer_gh', 'name' => null, 'active' => false],
        ],
    ]);

    expect($lookup->paymentMethod())->toBe('bank_transfer')
        ->and($lookup->currency()->code())->toBe('NGN')
        ->and($lookup->countryCode())->toBe('NG')
        ->and($lookup->rails())->toHaveCount(2)
        ->and($lookup->rails()[0])->toBeInstanceOf(PaymentRailOption::class)
        ->and($lookup->rails()[0]->name())->toBe('Bank Transfer Nigeria')
        ->and($lookup->rails()[0]->active())->toBeTrue()
        ->and($lookup->rails()[1]->name())->toBeNull()
        ->and($lookup->rails()[1]->active())->toBeFalse();
});

it('hydrates supported currencies grouped by type', function () {
    $currencies = SupportedCurrencies::from([
        'fiat' => ['USD', 'NGN', 'GHS', 'KES'],
        'crypto' => ['USDT_TRC20', 'BTC'],
    ]);

    expect($currencies->fiat())->toHaveCount(4)
        ->and($currencies->crypto())->toHaveCount(2)
        ->and($currencies->all())->toHaveCount(6)
        ->and($currencies->supports('NGN'))->toBeTrue()
        ->and($currencies->supports('USDT_TRC20'))->toBeTrue()
        ->and($currencies->supports('EUR'))->toBeFalse()
        ->and($currencies->isEmpty())->toBeFalse();
});

it('hydrates balances with money buckets', function () {
    $balance = Balance::from([
        'account_id' => 'org_1',
        'balances' => [
            ['currency' => 'NGN', 'available_balance' => '250000.00', 'pending_balance' => '15000.00'],
            ['currency' => 'USD', 'available_balance' => '1200.00', 'pending_balance' => '175.00'],
        ],
        'total_balance_usd' => '1375.00',
        'pending_settlements_by_day' => [],
    ]);

    expect($balance->accountId())->toBe('org_1')
        ->and($balance->balances())->toHaveCount(2)
        ->and($balance->balances()[0])->toBeInstanceOf(BalanceBucket::class)
        ->and($balance->balances()[0]->currency()->code())->toBe('NGN')
        ->and($balance->balances()[0]->availableBalance()->amount())->toBe('250000.00')
        ->and($balance->balances()[0]->pendingBalance()->amount())->toBe('15000.00')
        ->and($balance->totalBalanceUsd())->toBeInstanceOf(Money::class)
        ->and($balance->totalBalanceUsd()->amount())->toBe('1375.00')
        ->and($balance->pendingSettlementsByDay())->toBe([]);
});

it('hydrates uploads using the upload_id field as the id', function () {
    $upload = Upload::from([
        'upload_id' => 'upl_1',
        'provider' => 's3',
        'file_name' => 'product-hero.png',
        'mime_type' => 'image/png',
        'file_size_bytes' => 204800,
        'url' => 'https://cdn.bachs.io/uploads/upl_1/product-hero.png',
        'linked_resource_type' => 'product',
        'linked_resource_id' => 'prod_1',
        'created_at' => '2026-07-13T14:00:00.000Z',
    ]);

    expect($upload->id())->toBe('upl_1')
        ->and($upload->provider())->toBe('s3')
        ->and($upload->fileName())->toBe('product-hero.png')
        ->and($upload->mimeType())->toBe('image/png')
        ->and($upload->fileSizeBytes())->toBe(204800)
        ->and($upload->url())->toBe('https://cdn.bachs.io/uploads/upl_1/product-hero.png')
        ->and($upload->linkedResourceType())->toBe('product')
        ->and($upload->linkedResourceId())->toBe('prod_1');
});
