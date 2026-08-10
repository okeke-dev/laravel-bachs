<?php

use OkekeDev\Bachs\BachsClient;
use OkekeDev\Bachs\BachsManager;
use OkekeDev\Bachs\Contracts\BachsFactory;
use OkekeDev\Bachs\Support\BaseUrl;

it('creates a client from a connection configuration', function () {
    $app = app();

    $app['config']->set('bachs.connections.custom', [
        'secret' => 'sk_sandbox_custom',
        'env' => 'sandbox',
        'base_url' => 'https://custom.example.test/v1',
        'timeout' => 15,
    ]);

    $manager = new BachsManager($app);
    $client = $manager->connection('custom');

    expect($client)->toBeInstanceOf(BachsClient::class)
        ->and($client->secret())->toBe('sk_sandbox_custom')
        ->and($client->baseUrl())->toBe('https://custom.example.test/v1')
        ->and($client->config()['timeout'])->toBe(15);
});

it('exposes the resolved configuration on the client', function () {
    $client = app(BachsFactory::class)->connection();

    expect($client->config())->toBeArray()
        ->and($client->config())->toHaveKey('secret')
        ->and($client->config())->toHaveKey('env')
        ->and($client->config())->toHaveKey('base_url');
});

it('forwards dynamic calls to the default connection', function () {
    $manager = new BachsManager(app());

    expect($manager->baseUrl())->toBe(BaseUrl::SANDBOX);
});
