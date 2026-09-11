<?php

namespace Saucebase\Core;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use InterNACHI\Modular\Support\Facades\Modules;
use InterNACHI\Modular\Support\ModularizedCommandsServiceProvider;
use InterNACHI\Modular\Support\ModularServiceProvider;
use Saucebase\Core\Console\Commands\GenerateModuleTypesCommand;
use Saucebase\Core\Console\Commands\RecipeToModuleCommand;
use Saucebase\Core\Console\Commands\SeedModulesCommand;
use Saucebase\Core\Providers\BreadcrumbServiceProvider;
use Saucebase\Core\Providers\ConfigServiceProvider;
use Saucebase\Core\Providers\FilamentServiceProvider;
use Saucebase\Core\Providers\InertiaServiceProvider;
use Saucebase\Core\Providers\LocalizationServiceProvider;
use Saucebase\Core\Providers\ModalServiceProvider;
use Saucebase\Core\Providers\NavigationServiceProvider;
use Saucebase\Core\Providers\SecurityServiceProvider;
use Saucebase\Core\Providers\SettingsServiceProvider;

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
        ConfigServiceProvider::class,
        FilamentServiceProvider::class,
        NavigationServiceProvider::class,
        BreadcrumbServiceProvider::class,
        InertiaServiceProvider::class,
        ModalServiceProvider::class,
        SettingsServiceProvider::class,
        LocalizationServiceProvider::class,
        SecurityServiceProvider::class,
    ];

    public function register(): void
    {
        $this->registerModular();

        foreach (self::PROVIDERS as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Migrations and commands load always; publishing is console-only.
     *
     * Laravel only auto-loads commands out of the application's own
     * app/Console/Commands directory, so a command shipped by a package is invisible
     * until it is registered here.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Commands are registered unconditionally, not behind runningInConsole().
        // `Artisan::call()` works from an HTTP request too — the end-to-end suite drives
        // `migrate:fresh` and `modules:seed` that way — and a command that only exists
        // in the console is simply not found there. `commands()` defers through
        // `Artisan::starting()` anyway, so there is nothing to save by guarding it.
        $this->commands([
            GenerateModuleTypesCommand::class,
            RecipeToModuleCommand::class,
            SeedModulesCommand::class,
        ]);

        if (! $this->app->runningInConsole()) {
            return;
        }

        // Publishing is opt-in ownership, not a duplicate: the migrator keys files by
        // migration name across every registered path, so a published copy replaces
        // core's rather than running alongside it.
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'saucebase-migrations');

        // Brand assets have to be copied, not loaded: the web server reads public/ off
        // disk, so there is no vendor equivalent of loadMigrationsFrom() for them.
        // Publishing is not forced, so an application that has replaced these keeps its
        // own — which is how the app repo and demo/ stay Saucebase-branded.
        $this->publishes([
            __DIR__.'/../public/images' => public_path('images'),
        ], 'saucebase-assets');
    }

    /**
     * Registers internachi/modular by hand, after its config.
     *
     * `ModularServiceProvider::register()` reads `modules_directory` and memoises the
     * path, so it has to see our value first — hence `dont-discover` in composer.json
     * and these lines in this order. Separate them and modules silently resolve
     * against `app-modules/` instead, with no error.
     *
     * Suppressing discovery also drops the facade alias, so it is re-registered here.
     */
    private function registerModular(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/app-modules.php', 'app-modules');

        $this->app->register(ModularServiceProvider::class);
        $this->app->register(ModularizedCommandsServiceProvider::class);

        AliasLoader::getInstance()->alias('Modules', Modules::class);
    }
}
