<?php

namespace Saucebase\Core\Tests\Concerns;

use Composer\Autoload\ClassLoader;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;

/**
 * Installs tests/Fixtures/modules the way Composer installs a real one.
 *
 * Opt-in, because a discovered module contributes navigation entries, settings sections
 * and a Filament plugin, and the rest of the suite asserts against a bare install.
 *
 * A module reaches a real application through its own composer.json: Composer reads
 * `autoload.psr-4` to map its namespace, and Laravel reads `extra.laravel.providers` to
 * register its provider. Nothing installs these fixtures, so both steps happen here —
 * off the same file, so the fixture stays a module rather than becoming a special case
 * that core's own manifest has to know about.
 *
 * Both methods override the ones these tests inherit: a trait takes precedence over an
 * inherited method, so neither TestCase nor testbench needs to know modules exist.
 */
trait InteractsWithFixtureModules
{
    /**
     * Where the modules directory is, decided before any provider registers.
     *
     * This cannot go in defineEnvironment(): testbench runs that *after* its
     * RegisterProviders bootstrapper, and a module's provider resolves ModuleRegistry
     * while registering — which is the moment internachi/modular reads this key and
     * memoises the resulting path.
     *
     * @param  Application  $app
     */
    protected function resolveApplicationConfiguration($app): void
    {
        $this->autoloadFixtureModules();

        parent::resolveApplicationConfiguration($app);

        $app->make(Repository::class)->set(
            'app-modules.modules_directory',
            $this->fixtureModulesDirectory($app),
        );
    }

    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), ...$this->fixtureModuleProviders()];
    }

    /**
     * Map each fixture's namespace onto its src directory, as an install would.
     *
     * `vendor/autoload.php` hands back the ClassLoader already serving this process
     * rather than building a second one, so the prefixes land on the live autoloader.
     */
    private function autoloadFixtureModules(): void
    {
        /** @var ClassLoader $loader */
        $loader = require dirname(__DIR__, 2).'/vendor/autoload.php';

        foreach ($this->fixtureModuleManifests() as $directory => $manifest) {
            foreach ($manifest['autoload']['psr-4'] ?? [] as $namespace => $source) {
                $loader->addPsr4($namespace, $directory.'/'.$source);
            }
        }
    }

    /**
     * @return list<class-string>
     */
    private function fixtureModuleProviders(): array
    {
        return collect($this->fixtureModuleManifests())
            ->flatMap(fn (array $manifest): array => $manifest['extra']['laravel']['providers'] ?? [])
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fixtureModuleManifests(): array
    {
        $manifests = [];

        foreach (glob(dirname(__DIR__).'/Fixtures/modules/*/composer.json') ?: [] as $file) {
            $manifests[dirname($file)] = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        }

        return $manifests;
    }

    /**
     * The fixtures as a path relative to the application's base path.
     *
     * Both ModuleRegistry and the module_path() helper resolve the modules directory
     * through base_path(), which concatenates rather than resolving — so it cannot be
     * handed an absolute path. The fixtures live in this package and testbench's base
     * path is inside its own vendor directory, so the two only meet by walking up.
     */
    private function fixtureModulesDirectory(Application $app): string
    {
        $from = explode('/', trim(str_replace('\\', '/', $app->basePath()), '/'));
        $to = explode('/', trim(str_replace('\\', '/', dirname(__DIR__).'/Fixtures/modules'), '/'));

        while ($from !== [] && $to !== [] && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return implode('/', [...array_fill(0, count($from), '..'), ...$to]);
    }
}
