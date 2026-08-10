<?php

namespace OkekeDev\Bachs\Facades;

use Illuminate\Support\Facades\Facade;
use OkekeDev\Bachs\BachsManager;
use OkekeDev\Bachs\Contracts\BachsFactory;

/**
 * @method static \OkekeDev\Bachs\BachsClient connection(string|null $name = null)
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
