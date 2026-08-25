<?php

use OkekeDev\Bachs\Http\BachsRequest;

it('identifies GET as a safe method', function () {
    $request = new BachsRequest(method: 'GET', path: 'products');

    expect($request->isSafeMethod())->toBeTrue();
});

it('identifies HEAD as a safe method', function () {
    $request = new BachsRequest(method: 'HEAD', path: 'products');

    expect($request->isSafeMethod())->toBeTrue();
});

it('identifies OPTIONS as a safe method', function () {
    $request = new BachsRequest(method: 'OPTIONS', path: 'products');

    expect($request->isSafeMethod())->toBeTrue();
});

it('identifies POST as not safe', function () {
    $request = new BachsRequest(method: 'POST', path: 'customers');

    expect($request->isSafeMethod())->toBeFalse();
});

it('identifies PUT as not safe', function () {
    $request = new BachsRequest(method: 'PUT', path: 'customers/cust_1');

    expect($request->isSafeMethod())->toBeFalse();
});

it('identifies PATCH as not safe', function () {
    $request = new BachsRequest(method: 'PATCH', path: 'customers/cust_1');

    expect($request->isSafeMethod())->toBeFalse();
});

it('identifies DELETE as not safe', function () {
    $request = new BachsRequest(method: 'DELETE', path: 'customers/cust_1');

    expect($request->isSafeMethod())->toBeFalse();
});

it('reports no idempotency key when null', function () {
    $request = new BachsRequest(method: 'POST', path: 'customers');

    expect($request->hasIdempotencyKey())->toBeFalse();
});

it('reports idempotency key when provided', function () {
    $request = new BachsRequest(method: 'POST', path: 'customers', idempotencyKey: 'idem_123');

    expect($request->hasIdempotencyKey())->toBeTrue()
        ->and($request->idempotencyKey)->toBe('idem_123');
});

it('stores query parameters', function () {
    $request = new BachsRequest(method: 'GET', path: 'products', query: ['limit' => 20, 'status' => 'active']);

    expect($request->query)->toBe(['limit' => 20, 'status' => 'active']);
});

it('stores body data', function () {
    $request = new BachsRequest(method: 'POST', path: 'customers', body: ['email' => 'a@b.com']);

    expect($request->body)->toBe(['email' => 'a@b.com']);
});

it('stores custom headers', function () {
    $request = new BachsRequest(method: 'GET', path: 'products', headers: ['X-Custom' => 'value']);

    expect($request->headers)->toBe(['X-Custom' => 'value']);
});

it('stores attachments', function () {
    $attachments = [
        ['name' => 'file', 'contents' => 'binary data', 'filename' => 'test.pdf'],
    ];

    $request = new BachsRequest(method: 'POST', path: 'media', attachments: $attachments);

    expect($request->attachments)->toBe($attachments);
});

it('stores the method and path', function () {
    $request = new BachsRequest(method: 'POST', path: 'customers/cust_1/subscriptions');

    expect($request->method)->toBe('POST')
        ->and($request->path)->toBe('customers/cust_1/subscriptions');
});

it('defaults empty arrays for optional parameters', function () {
    $request = new BachsRequest(method: 'GET', path: 'products');

    expect($request->query)->toBe([])
        ->and($request->body)->toBe([])
        ->and($request->headers)->toBe([])
        ->and($request->attachments)->toBe([])
        ->and($request->idempotencyKey)->toBeNull();
});
