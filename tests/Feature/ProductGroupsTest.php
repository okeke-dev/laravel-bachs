<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Collections\PaginatedCollection;
use OkekeDev\Bachs\Resources\ProductGroups;

it('creates a product group', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/product-groups' => Http::response([
            'id' => 'pgrp_1',
            'name' => 'Merch',
        ], 201),
    ]);

    $group = ProductGroups::create(['name' => 'Merch', 'description' => 'T-shirts and hoodies']);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://sandbox-api.bachs.io/v1/product-groups'
            && $request['name'] === 'Merch';
    });

    expect($group['id'])->toBe('pgrp_1');
});

it('lists product groups as a paginated collection', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/product-groups*' => Http::response([
            'items' => [['id' => 'pgrp_1', 'name' => 'Merch']],
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

    $groups = ProductGroups::list();

    expect($groups)->toBeInstanceOf(PaginatedCollection::class)
        ->and($groups->count())->toBe(1)
        ->and($groups->total())->toBe(1)
        ->and($groups->first()['name'])->toBe('Merch');
});

it('fetches, updates, and deletes a product group', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/product-groups/pgrp_1' => Http::sequence()
            ->push(['id' => 'pgrp_1', 'name' => 'Merch'], 200)
            ->push(['id' => 'pgrp_1', 'name' => 'Merch & More'], 200)
            ->push(['id' => 'pgrp_1', 'deleted' => true], 200),
    ]);

    expect(ProductGroups::get('pgrp_1')['name'])->toBe('Merch');

    $updated = ProductGroups::update('pgrp_1', ['name' => 'Merch & More']);
    expect($updated['name'])->toBe('Merch & More');

    expect(ProductGroups::delete('pgrp_1')['deleted'])->toBeTrue();
});
