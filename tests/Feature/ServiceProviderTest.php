<?php

use OkekeDev\Bachs\BachsClient;
use OkekeDev\Bachs\Contracts\BachsFactory;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Support\BaseUrl;

it('binds the manager into the container as the Bachs factory', function () {
    expect(app(BachsFactory::class))->toBeInstanceOf(BachsFactory::class);
});

it('aliases the factory as "bachs"', function () {
    expect(app('bachs'))->toBeInstanceOf(BachsFactory::class);
});

it('resolves the default connection from configuration', function () {
    $connection = app(BachsFactory::class)->connection();

    expect($connection)->toBeInstanceOf(BachsClient::class)
        ->and($connection->secret())->toBe('sk_sandbox_test_secret')
        ->and($connection->baseUrl())->toBe(BaseUrl::SANDBOX);
});

it('resolves and caches connections as singletons', function () {
    $manager = app(BachsFactory::class);

    expect($manager->connection())->toBe($manager->connection());
});

it('throws when an unknown connection is requested', function () {
    app(BachsFactory::class)->connection('missing');
})->throws(BachsInvalidArgumentException::class);
