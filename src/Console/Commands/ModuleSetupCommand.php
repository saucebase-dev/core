<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use Symfony\Component\Console\Input\StringInput;

/**
 * Wires installed modules into the application. `saucebase:module-install` runs it in a
 * new process. Safe to run again.
 */
class ModuleSetupCommand extends Command
{
    protected $signature = 'saucebase:module-setup
                            {modules* : Installed module names}
                            {--demo : Also seed demo content}';

    protected $description = 'Apply patches, migrate and seed installed modules';

    protected $hidden = true;

    public function handle(ModuleRegistry $registry): int
    {
        $modules = [];

        foreach ($this->argument('modules') as $name) {
            if (! $modules[] = $registry->module($name)) {
                $this->components->error("Module \"{$name}\" is not installed.");

                return self::FAILURE;
            }
        }

        $conflicts = $this->applyPatches($modules);

        $steps = [['migrate', ['--force' => true]]];

        foreach ($modules as $module) {
            $steps[] = ['modules:seed', ['--module' => $module->name, '--demo' => $this->option('demo')]];
        }

        // Each module's own commands come last, once every module is migrated and seeded.
        foreach ($modules as $module) {
            $composer = json_decode((string) @file_get_contents($module->path('composer.json')), true);

            foreach ((array) data_get($composer, 'extra.saucebase.post-install', []) as $line) {
                // Artisan only: a module's own code, never a shell command from a dependency.
                if (! str_starts_with($line, 'php artisan ')) {
                    $this->components->error("{$module->name}: post-install runs artisan commands only: {$line}");

                    return self::FAILURE;
                }

                $steps[] = [substr($line, strlen('php artisan ')), null];
            }
        }

        foreach ($steps as [$command, $arguments]) {
            if ($this->runStep($command, $arguments) !== self::SUCCESS) {
                $this->components->error("`{$command}` failed. Fix it, then run this again: php artisan saucebase:module-setup ".implode(' ', $this->argument('modules')).($this->option('demo') ? ' --demo' : ''));

                return self::FAILURE;
            }
        }

        $this->components->info('Modules installed. Run: npm install && npm run build');

        // Last, so it isn't scrolled away. The patches only add traits and interfaces the
        // module needs at runtime, so nothing above depended on them.
        foreach ($conflicts as $patch) {
            $this->components->warn("{$patch} did not apply. Make its change by hand: see the module's README, \"Manual install\".");
        }

        return self::SUCCESS;
    }

    /**
     * A post-install line keeps its own options (`tenancy:install --mode=path`), and
     * asks its questions when this command can.
     *
     * @param  array<string, mixed>|null  $arguments  Null for a post-install line.
     */
    private function runStep(string $command, ?array $arguments): int
    {
        if ($arguments !== null) {
            return $this->call($command, $arguments);
        }

        $input = new StringInput($command);
        $input->setInteractive($this->input->isInteractive());

        return $this->getApplication()->find((string) $input->getFirstArgument())->run($input, $this->output);
    }

    /**
     * A patch already in place is skipped. One that doesn't apply, usually because the
     * user changed the file, is left for them to make by hand.
     *
     * @param  ModuleConfig[]  $modules
     * @return string[] The patches that did not apply, as `<module>/patches/<file>`.
     */
    private function applyPatches(array $modules): array
    {
        $conflicts = [];

        foreach ($modules as $module) {
            foreach (glob($module->path('patches').'/*.patch') ?: [] as $patch) {
                if (! $this->alreadyApplied($patch) && ! $this->gitApply($patch)) {
                    $conflicts[] = "{$module->name}/patches/".basename($patch);
                }
            }
        }

        return $conflicts;
    }

    /**
     * Applies in reverse, or every line it adds is already in the file it changes: a
     * formatter sorts imports and traits, and git then no longer recognises the patch.
     */
    private function alreadyApplied(string $patch): bool
    {
        if ($this->gitApply($patch, '--reverse', '--check')) {
            return true;
        }

        $file = null;

        foreach (explode("\n", (string) file_get_contents($patch)) as $line) {
            if (str_starts_with($line, '+++ b/')) {
                // Whole lines: `// use Billable;` must not count as `use Billable;`.
                $file = array_map(trim(...), explode("\n", (string) @file_get_contents(base_path(substr($line, 6)))));

                continue;
            }

            $added = trim(substr($line, 1));

            if ($file !== null && str_starts_with($line, '+') && $added !== '' && ! in_array($added, $file, true)) {
                return false;
            }
        }

        return $file !== null;
    }

    private function gitApply(string $patch, string ...$flags): bool
    {
        return Process::path(base_path())->run(['git', 'apply', '--whitespace=nowarn', ...$flags, $patch])->successful();
    }
}
