<?php

namespace Saucebase\Core;

use Illuminate\Support\ServiceProvider;

/**
 * The single provider Laravel discovers for this package.
 *
 * Everything Saucebase adds on top of bare Laravel is registered from here, one
 * sub-provider per concern. Keeping the list explicit rather than scanning a
 * directory means the boot order is readable, which matters: navigation has to be
 * bound before the Inertia middleware shares the tree, and module settings have to
 * be discovered before the settings container registers its bindings.
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Sub-providers, in boot order.
     *
     * @var list<class-string<ServiceProvider>>
     */
    private const PROVIDERS = [
        //
    ];

    public function register(): void
    {
        foreach (self::PROVIDERS as $provider) {
            $this->app->register($provider);
        }
    }
}
