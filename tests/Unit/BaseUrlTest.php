<?php

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Support\BaseUrl;

it('resolves the sandbox URL from the environment', function () {
    expect(BaseUrl::resolve(['env' => 'sandbox']))->toBe(BaseUrl::SANDBOX);
});

it('resolves the live URL from the environment', function () {
    expect(BaseUrl::resolve(['env' => 'live']))->toBe(BaseUrl::LIVE);
});

it('infers the environment from the secret key prefix when env is not configured', function () {
    expect(BaseUrl::resolve(['secret' => 'sk_sandbox_123']))->toBe(BaseUrl::SANDBOX);
    expect(BaseUrl::resolve(['secret' => 'sk_live_123']))->toBe(BaseUrl::LIVE);
});

it('rejects a secret key prefix that contradicts the configured env', function () {
    BaseUrl::resolve(['env' => 'sandbox', 'secret' => 'sk_live_123']);
})->throws(BachsInvalidArgumentException::class);

it('uses the env when no secret key is configured', function () {
    expect(BaseUrl::resolve(['env' => 'live', 'secret' => '']))->toBe(BaseUrl::LIVE);
});

it('prefers an explicit base url override', function () {
    $url = 'https://bachs.example.test/v1';

    expect(BaseUrl::resolve([
        'env' => 'live',
        'base_url' => $url,
    ]))->toBe($url);
});

it('strips a trailing slash from an explicit base url', function () {
    expect(BaseUrl::resolve([
        'env' => 'live',
        'base_url' => 'https://bachs.example.test/v1/',
    ]))->toBe('https://bachs.example.test/v1');
});

it('uses the configured api version segment', function () {
    expect(BaseUrl::resolve(['env' => 'sandbox', 'api_version' => 'v2']))
        ->toBe('https://sandbox-api.bachs.io/v2');

    expect(BaseUrl::resolve(['env' => 'live', 'api_version' => 'v2']))
        ->toBe('https://api.bachs.io/v2');
});

it('defaults to v1 when no api version is configured', function () {
    expect(BaseUrl::resolve(['env' => 'sandbox', 'secret' => 'sk_sandbox_123']))
        ->toBe(BaseUrl::SANDBOX);
});

it('applies the api version to key-prefixed environments too', function () {
    expect(BaseUrl::resolve(['secret' => 'sk_live_123', 'api_version' => 'v2']))
        ->toBe('https://api.bachs.io/v2');
});

it('keeps an explicit base url immune to the api version setting', function () {
    expect(BaseUrl::resolve([
        'env' => 'live',
        'base_url' => 'https://bachs.example.test/v1',
        'api_version' => 'v9',
    ]))->toBe('https://bachs.example.test/v1');
});
