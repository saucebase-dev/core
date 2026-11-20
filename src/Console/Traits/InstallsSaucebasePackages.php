<?php

namespace Saucebase\Core\Console\Traits;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * The steps `saucebase:module-install` and `saucebase:template-install` share:
 * require with Composer, read what it locked, then set up in a new process.
 */
trait InstallsSaucebasePackages
{
    /**
     * Composer reverts its own failures, but not one in a plugin or script after it wrote
     * the lock: composer.json, composer.lock and modules/ are put back here either way.
     *
     * @param  string[]  $packages  vendor/name
     */
    protected function requirePackages(array $packages): bool
    {
        $saved = collect(['composer.json', 'composer.lock'])
            ->mapWithKeys(fn (string $file) => [base_path($file) => @file_get_contents(base_path($file))]);
        $modules = $this->moduleFolders();

        if (Process::path(base_path())->forever()
            ->run(['composer', 'require', ...$packages, '--no-interaction'], $this->writeOutput(...))
            ->successful()) {
            return true;
        }

        $saved->filter(fn ($contents) => $contents !== false)
            ->each(fn (string $contents, string $path) => file_put_contents($path, $contents));

        // vendor/, a module Composer upgraded, and the generated autoloader still follow the
        // new lock: installing from the restored one puts them back with it.
        if ($saved[base_path('composer.lock')] !== false) {
            Process::path(base_path())->forever()->run(['composer', 'install', '--no-interaction'], $this->writeOutput(...));
        }

        // Folders this run unpacked: left behind, core refuses to boot (UnloadableModules).
        foreach (array_diff($this->moduleFolders(), $modules) as $folder) {
            (new Filesystem)->deleteDirectory(base_path("modules/{$folder}"));
        }

        return false;
    }

    /** @return string[] */
    private function moduleFolders(): array
    {
        return array_map('basename', glob(base_path('modules/*'), GLOB_ONLYDIR) ?: []);
    }

    /**
     * A new process, the first to load the packages Composer just installed.
     * TTY, so a module's own install command can ask its questions.
     *
     * `--configure`: these modules are being installed now, so their install settings
     * are set (a template's `configure`, the `--set` flags, then prompts). A plain re-run of
     * `saucebase:module-setup` leaves settings an admin may have changed since alone.
     *
     * @param  string[]  $modules  In the order to set them up.
     */
    protected function runSetup(array $modules, ?string $state, bool $force): bool
    {
        $settings = array_map(fn (string $set) => "--set={$set}", (array) $this->option('set'));

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
