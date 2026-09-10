<?php

namespace Saucebase\Core\Navigation;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Saucebase\Core\Facades\Navigation as NavigationFacade;
use Spatie\Navigation\Helpers\ActiveUrlChecker;

class NavigationServiceProvider extends ServiceProvider
{
    /**
     * There is deliberately no boot() here.
     *
     * The navigation tree is built by the `navigation` shared prop in
     * HandleInertiaRequests, not at boot: it has to resolve after every module has
     * registered its items, and module providers boot after this one.
     */
    public function register(): void
    {
        // Scoped rather than singleton: the tree caches its loaded state and its items'
        // active flags are relative to the current request.
        $this->app->scoped(Navigation::class, fn ($app) => new Navigation(
            $app->make(ActiveUrlChecker::class),
        ));

        // Anything type-hinting Spatie's class gets ours.
        $this->app->alias(Navigation::class, \Spatie\Navigation\Navigation::class);

        AliasLoader::getInstance(['Navigation' => NavigationFacade::class]);
    }
}
