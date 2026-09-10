<?php

namespace Saucebase\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Saucebase\Core\Services\Navigation
 */
class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Saucebase\Core\Services\Navigation::class;
    }
}
