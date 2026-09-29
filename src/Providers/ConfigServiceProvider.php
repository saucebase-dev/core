<?php

namespace Saucebase\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Saucebase\Core\TypeScript\TypeScriptConfig;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

/**
 * Applies the handful of third-party config values Saucebase actually diverges on,
 * so the app does not have to ship a copy of each package's config file to change
 * two keys.
 *
 * Deliberately `boot()`, not `register()`, and deliberately `config()->set()`, not
 * `mergeConfigFrom()`. Both choices exist for the same reason:
 *
 * `mergeConfigFrom()` gives precedence to whatever is already loaded, so it can only
 * add missing keys — it cannot override a value a vendor package has already merged.
 * Used here it would silently do nothing, and the app would run on vendor defaults
 * with no error to notice.
 *
 * `boot()` is the first point at which every package's `register()` has finished, so
 * an explicit `set()` here is the last word regardless of provider discovery order.
 *
 * The corollary is the one thing this class cannot do: a key some package reads
 * *during its own* `register()` is already frozen by the time we boot. That needs the
 * heavier approach instead — suppress the package's discovery and register it by hand,
 * as CoreServiceProvider::registerModular() does for internachi/modular.
 */
class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/saucebase.php', 'saucebase');

        // Shipped whole, not as overrides: the exporter package only publishes its
        // config and never merges it, so any key core does not supply resolves to null.
        $this->mergeConfigFrom(
            __DIR__.'/../../config/laravel-translatable-string-exporter.php',
            'laravel-translatable-string-exporter',
        );

        $this->registerTypeScriptTransformer();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/saucebase.php' => config_path('saucebase.php'),
            __DIR__.'/../../config/laravel-translatable-string-exporter.php' => config_path('laravel-translatable-string-exporter.php'),
        ], 'saucebase-config');

        $this->configureFilament();
    }

    /**
     * Filament has no notion of Saucebase modules, so this key is ours alone —
     * read by `ModulesPlugin` and `ModulePlugin` to decide whether module pages
     * are grouped into clusters.
     */
    private function configureFilament(): void
    {
        config()->set('filament.modules.clusters', [
            'enabled' => true,
            'use-top-navigation' => false,
        ]);
    }

    /**
     * typescript-transformer v3 reads a config object from the container rather than
     * config keys. The app's own types go to `resources/js/types/`, where Vite can see
     * them; `module:generate-types` swaps in a per-module config the same way.
     */
    private function registerTypeScriptTransformer(): void
    {
        $this->app->singleton(TypeScriptTransformerConfig::class, fn () => TypeScriptConfig::make(
            app_path(),
            resource_path('js/types'),
        ));
    }
}
