<?php

namespace Saucebase\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Saucebase\Core\Services\Home
 */
class Home extends Facade
{
    public const VISIT = \Saucebase\Core\Services\Home::VISIT;

    public const LOGIN = \Saucebase\Core\Services\Home::LOGIN;

    public const REGISTERED = \Saucebase\Core\Services\Home::REGISTERED;

    public const VERIFIED = \Saucebase\Core\Services\Home::VERIFIED;

    protected static function getFacadeAccessor(): string
    {
        return \Saucebase\Core\Services\Home::class;
    }
}
