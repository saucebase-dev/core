<?php

namespace Saucebase\Core\Console\Traits;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * The steps `saucebase:module-install` and `saucebase:template-install` share:
 * require with Composer, read what it locked, then set up in a new process.
 */
trait InstallsSaucebasePackages
{
    /** @param  string[]  $packages  vendor/name */
    protected function requirePackages(array $packages): bool
    {
        return Process::path(base_path())->forever()
            ->run(['composer', 'require', ...$packages, '--no-interaction'], $this->writeOutput(...))
            ->successful();
    }

    /**
     * A new process, the first to load the packages Composer just installed.
     * TTY, so a module's own install command can ask its questions.
     *
     * `--configure`: these modules are being installed now, so their install settings
     * are set (a template's, the `--setting` flags, then prompts). A plain re-run of
     * `saucebase:module-setup` leaves settings an admin may have changed since alone.
     *
     * @param  string[]  $modules  In the order to set them up.
     */
    protected function runSetup(array $modules, ?string $state, bool $force): bool
    {
        $settings = array_map(fn (string $setting) => "--setting={$setting}", (array) $this->option('setting'));

        return Process::path(base_path())->forever()->tty(SymfonyProcess::isTtySupported() && $this->input->isInteractive())
            ->run([PHP_BINARY, 'artisan', 'saucebase:module-setup', ...$modules, ...($state ? ["--state={$state}"] : []), ...($force ? ['--force'] : []), '--configure', ...$settings], $this->writeOutput(...))
            ->successful();
    }

    /**
     * The state's seeders, in a new process for the same reason as setup.
     *
     * @param  string[]  $modules
     */
    protected function seedState(array $modules, string $state, bool $force): bool
    {
        return Process::path(base_path())->forever()->tty(SymfonyProcess::isTtySupported() && $this->input->isInteractive())
            ->run([PHP_BINARY, 'artisan', 'modules:seed', ...array_map(fn (string $module) => "--module={$module}", $modules), "--state={$state}", ...($force ? ['--force'] : [])], $this->writeOutput(...))
            ->successful();
    }

    /** @return array<string, array<string, mixed>> Each Saucebase module or template in composer.lock, by short name. */
    protected function lockedPackages(): array
    {
        return collect(json_decode((string) @file_get_contents(base_path('composer.lock')), true)['packages'] ?? [])
            ->whereIn('type', ['saucebase-module', 'saucebase-template'])
            ->keyBy(fn (array $package): string => Str::after($package['name'], '/'))
            ->all();
    }

    /** @return array<string, string[]> Each Saucebase module or template in composer.lock => the modules it requires. */
    protected function lockedModules(): array
    {
        $packages = $this->lockedPackages();
        $names = array_column($packages, 'name');

        return collect($packages)->map(fn (array $package): array => collect(array_keys($package['require'] ?? []))
            ->intersect($names)
            ->map(fn (string $name): string => Str::after($name, '/'))
            ->values()
            ->all())->all();
    }

    private function writeOutput(string $type, string $output): void
    {
        $this->output->write($output);
    }
}
