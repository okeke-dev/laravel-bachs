<?php

use OkekeDev\Bachs\BachsClient;

it('returns a BachsClient from the bachs() helper', function () {
    expect(bachs())->toBeInstanceOf(BachsClient::class);
});

it('resolves a named connection via the bachs() helper', function () {
    app('config')->set('bachs.connections.partner', [
        'secret' => 'sk_sandbox_partner',
        'env' => 'sandbox',
        'base_url' => 'https://partner.test/v1',
    ]);

    $client = bachs('partner');

    expect($client)->toBeInstanceOf(BachsClient::class)
        ->and($client->secret())->toBe('sk_sandbox_partner')
        ->and($client->baseUrl())->toBe('https://partner.test/v1');
});
