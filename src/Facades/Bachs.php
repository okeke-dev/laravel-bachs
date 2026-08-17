<?php

namespace OkekeDev\Bachs\Facades;

use Illuminate\Support\Facades\Facade;
use OkekeDev\Bachs\BachsClient;
use OkekeDev\Bachs\BachsManager;
use OkekeDev\Bachs\Contracts\BachsFactory;

/**
 * @method static BachsClient connection(string|null $name = null)
 * @method static string secret()
 * @method static string baseUrl()
 * @method static array<string, mixed> config()
 * @method static \OkekeDev\Bachs\Http\BachsResponse get(string $path, array<string, mixed> $query = [])
 * @method static \OkekeDev\Bachs\Http\BachsResponse post(string $path, array<string, mixed> $body = [], string|null $idempotencyKey = null)
 * @method static \OkekeDev\Bachs\Http\BachsResponse patch(string $path, array<string, mixed> $body = [], string|null $idempotencyKey = null)
 * @method static \OkekeDev\Bachs\Http\BachsResponse put(string $path, array<string, mixed> $body = [], string|null $idempotencyKey = null)
 * @method static \OkekeDev\Bachs\Http\BachsResponse delete(string $path, array<string, mixed> $query = [], string|null $idempotencyKey = null)
 * @method static \OkekeDev\Bachs\Http\BachsResponse upload(string $path, array<int, array{name: string, contents: string, filename?: string}> $attachments, array<string, mixed> $body = [], string|null $idempotencyKey = null)
 * @method static \OkekeDev\Bachs\Http\BachsResponse request(string $method, string $path, array<string, mixed> $options = [])
 *
 * @see BachsManager
 */
class Bachs extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BachsFactory::class;
    }
}
