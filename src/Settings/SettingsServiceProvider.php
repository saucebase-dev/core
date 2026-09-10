<?php

namespace Saucebase\Core\Settings;

use Illuminate\Support\ServiceProvider;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use Spatie\LaravelSettings\SettingsContainer;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->booting(function (): void {
            $this->discoverSettings();

            $this->app->make(SettingsContainer::class)
                ->clearCache()
                ->registerBindings();
        });
    }

    /**
     * Tell Spatie where settings classes and their migrations live.
     *
     * Spatie's defaults cover the application only — app_path('Settings') and
     * database_path('settings'). Core's own settings and every module's have to be
     * appended, or they are simply never bound.
     */
    protected function discoverSettings(): void
    {
        $modules = $this->app->make(ModuleRegistry::class)->modules();

        $settingsPaths = $modules
            ->map(fn (ModuleConfig $module): string => $module->path('src/Settings'))
            ->prepend(__DIR__)
            ->filter(fn (string $path): bool => is_dir($path));

        $migrationPaths = $modules
            ->map(fn (ModuleConfig $module): string => $module->path('database/settings'))
            ->prepend(dirname(__DIR__, 2).'/database/settings')
            ->filter(fn (string $path): bool => is_dir($path));

        config()->set(
            'settings.auto_discover_settings',
            $this->normalizeUniquePaths([
                ...config('settings.auto_discover_settings', []),
                ...$settingsPaths,
            ]),
        );

        config()->set(
            'settings.migrations_paths',
            $this->normalizeUniquePaths([
                ...config('settings.migrations_paths', []),
                ...$migrationPaths,
            ]),
        );
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    protected function normalizeUniquePaths(array $paths): array
    {
        return collect($paths)
            ->map(fn (string $path): string => rtrim(str_replace('\\', '/', $path), '/'))
            ->unique()
            ->values()
            ->all();
    }
}
