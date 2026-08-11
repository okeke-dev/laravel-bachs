<?php

use OkekeDev\Bachs\Collections\PaginatedCollection;

it('builds from a Bachs list payload', function () {
    $collection = PaginatedCollection::fromPayload([
        'items' => [
            ['id' => 'prod_1'],
            ['id' => 'prod_2'],
        ],
        'pagination' => [
            'has_more' => true,
            'next_cursor' => 'cursor_2',
            'prev_cursor' => null,
            'limit' => 20,
            'offset' => 0,
            'returned' => 2,
            'total' => 5,
        ],
    ]);

    expect($collection->all())->toBe([
        ['id' => 'prod_1'],
        ['id' => 'prod_2'],
    ])
        ->and($collection->count())->toBe(2)
        ->and($collection->hasMore())->toBeTrue()
        ->and($collection->nextCursor())->toBe('cursor_2')
        ->and($collection->prevCursor())->toBeNull()
        ->and($collection->limit())->toBe(20)
        ->and($collection->offset())->toBe(0)
        ->and($collection->returned())->toBe(2)
        ->and($collection->total())->toBe(5)
        ->and($collection->pagination())->toMatchArray([
            'has_more' => true,
            'next_cursor' => 'cursor_2',
        ]);
});

it('handles an empty or malformed payload', function () {
    $collection = PaginatedCollection::fromPayload([]);

    expect($collection->all())->toBe([])
        ->and($collection->count())->toBe(0)
        ->and($collection->hasMore())->toBeFalse()
        ->and($collection->nextCursor())->toBeNull()
        ->and($collection->prevCursor())->toBeNull()
        ->and($collection->limit())->toBeNull()
        ->and($collection->offset())->toBeNull()
        ->and($collection->returned())->toBeNull()
        ->and($collection->total())->toBeNull()
        ->and($collection->pagination())->toBe([]);
});

it('maps items while preserving pagination metadata', function () {
    $collection = PaginatedCollection::fromPayload([
        'items' => [['id' => 'prod_1']],
        'pagination' => ['has_more' => true, 'next_cursor' => 'cursor_2', 'total' => 1],
    ]);

    $mapped = $collection->map(fn ($item) => $item['id']);

    expect($mapped)->toBeInstanceOf(PaginatedCollection::class)
        ->and($mapped->all())->toBe(['prod_1'])
        ->and($mapped->hasMore())->toBeTrue()
        ->and($mapped->nextCursor())->toBe('cursor_2');
});

it('applies a map callable when building from a payload', function () {
    $collection = PaginatedCollection::fromPayload([
        'items' => [['id' => 'prod_1']],
        'pagination' => [],
    ], fn ($item) => strtoupper($item['id']));

    expect($collection->all())->toBe(['PROD_1'])
        ->and($collection->pagination())->toBe([]);
});
