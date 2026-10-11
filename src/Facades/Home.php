<?php

namespace Saucebase\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Saucebase\Core\Home
 */
class Home extends Facade
{
    public const VISIT = \Saucebase\Core\Home::VISIT;

    public const LOGIN = \Saucebase\Core\Home::LOGIN;

    public const REGISTERED = \Saucebase\Core\Home::REGISTERED;

    public const VERIFIED = \Saucebase\Core\Home::VERIFIED;

    protected static function getFacadeAccessor(): string
    {
        return \Saucebase\Core\Home::class;
    }
}
