<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Saucebase\Core\Console\Traits\InstallsSaucebasePackages;
use Saucebase\Core\Support\DependencyOrder;

use function Laravel\Prompts\multiselect;

/**
 * Adds modules to an existing application: requires them, then runs
 * `saucebase:module-setup` in a new process, which is the first to load them.
 */
class ModuleInstallCommand extends Command
{
    use InstallsSaucebasePackages;

    protected $signature = 'saucebase:module-install
                            {modules?* : Module names (billing). Omit to pick from a list}
                            {--state= : Also seed a state (showcase: sample content)}
                            {--force : Seed in production without asking}
                            {--set=* : Configure a module, module.key=value (tenancy.mode=subdomain, auth.registration_enabled=false)}';

    protected $description = 'Install Saucebase modules into this application';

    public function handle(): int
    {
        $modules = $this->argument('modules') ?: ($this->input->isInteractive() ? $this->pick() : []);

        if ($modules === null) {
            $this->components->info('Every available module is already installed.');

            return self::SUCCESS;
        }

        if ($modules === []) {
            $this->components->error('Name the modules to install: php artisan saucebase:module-install billing');

            return self::FAILURE;
        }

        $packages = array_map(fn (string $name) => "saucebase/{$name}", $modules);

        $locked = $this->lockedModules();

        if (! $this->requirePackages($packages)) {
            $this->components->error('Composer could not install the modules. Nothing was changed: composer.json, composer.lock and modules/ are back as they were.');

            return self::FAILURE;
        }

        // A module another one requires (api-tokens needs auth) arrives with it, and each is
        // set up after the modules it requires, so their patches and tables exist first.
        $after = $this->lockedModules();
        $modules = DependencyOrder::sort([
            ...array_diff(array_keys($after), array_keys($locked)),
            ...$modules,
        ], $after);

        $force = (bool) $this->option('force');

        if (! $this->runSetup($modules, null, $force)) {
            return self::FAILURE;
        }

        // Last, in a process of its own, as template-install does: setup patches models
        // (api-tokens adds HasApiTokens to User) in a process that already loaded them.
        $state = $this->option('state');

        return $state === null || $this->seedState($modules, $state, $force) ? self::SUCCESS : self::FAILURE;
    }

    /** @return string[]|null Module names on Packagist the app doesn't require yet; null when it has them all. */
    private function pick(): ?array
    {
        $listed = Http::timeout(10)->get('https://packagist.org/packages/list.json?type=saucebase-module&fields[]=abandoned')->json('packages') ?? [];
        $required = array_keys(json_decode((string) @file_get_contents(base_path('composer.json')), true)['require'] ?? []);

        $options = collect($listed)
            ->reject(fn (array $package) => ! empty($package['abandoned']))
            ->keys()
            ->diff($required)
            ->mapWithKeys(fn (string $package) => [Str::after($package, '/') => Str::headline(Str::after($package, '/'))])
            ->all();

        if ($options === []) {
            return $listed === [] ? [] : null;
        }

        return multiselect('Which modules would you like to install?', $options);
    }
}
