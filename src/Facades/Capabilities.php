<?php

namespace Saucebase\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Saucebase\Core\Capabilities
 */
class Capabilities extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Saucebase\Core\Capabilities::class;
    }
}
