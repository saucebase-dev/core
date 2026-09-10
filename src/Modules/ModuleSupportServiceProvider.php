<?php

namespace Saucebase\Core\Modules;

use Illuminate\Support\ServiceProvider;

class ModuleSupportServiceProvider extends ServiceProvider
{
    /**
     * Commands have to be named, not discovered.
     *
     * Laravel only auto-loads commands out of the application's own
     * app/Console/Commands directory, so a command shipped by a package is invisible
     * until it is registered here.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            GenerateModuleTypesCommand::class,
            RecipeToModuleCommand::class,
            SeedModulesCommand::class,
        ]);
    }
}
