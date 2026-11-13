<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use Saucebase\Core\ModuleTaskfile;
use Saucebase\Core\ProjectConfig;
use Saucebase\Core\Settings\InstallSettings;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;

use function Laravel\Prompts\confirm;

/**
 * Wires installed modules into the application. `saucebase:module-install` runs it in a
 * new process. Safe to run again.
 */
class ModuleSetupCommand extends Command
{
    protected $signature = 'saucebase:module-setup
                            {modules* : Installed module names}
                            {--state= : Also seed a state (showcase: sample content)}
                            {--force : Seed in production without asking}
                            {--configure : These modules are being installed: set their install settings (a template\'s configure, --set, then prompts)}
                            {--set=* : Configure a module, module.key=value: an install setting or an answer for its install command (tenancy.mode=subdomain)}';

    protected $description = 'Apply patches, migrate and seed installed modules';

    protected $hidden = true;

    public function handle(ModuleRegistry $registry, ProjectConfig $config): int
    {
        $modules = [];

        foreach ($this->argument('modules') as $name) {
            if (! $modules[] = $registry->module($name)) {
                $this->components->error("Module \"{$name}\" is not installed.");

                return self::FAILURE;
            }
        }

        // Checked before anything is written, so a bad entry changes nothing.
        $plan = $this->configuration($modules);

        if (is_string($plan)) {
            $this->components->error($plan);

            return self::FAILURE;
        }

        $conflicts = $this->applyPatches($modules);

        $steps = [
            ['migrate', ['--force' => true]],
            // Together: seeding them one by one would hide each from the others' SeedAfter.
            ['modules:seed', ['--module' => array_map(fn ($module) => $module->name, $modules), '--state' => $this->option('state'), '--force' => $this->option('force')]],
        ];

        foreach ($modules as $module) {
            if (file_exists($module->path('Taskfile.yml'))) {
                ModuleTaskfile::add($module->name);
            }
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

                $steps[] = [substr($line, strlen('php artisan ')).$this->appendOptions($line, $plan[$module->name]['answers']), null];
            }
        }

        foreach ($steps as $index => [$command, $arguments]) {
            $failed = $this->runStep($command, $arguments) !== self::SUCCESS;

            // Once migrated (the settings exist), before seeders read them.
            if (! $failed && $index === 0 && $this->option('configure')) {
                $this->applySettings($plan);
            }

            if ($failed) {
                $this->components->error("`{$command}` failed. Fix it, then run this again: php artisan saucebase:module-setup ".implode(' ', $this->argument('modules')).($this->option('state') ? ' --state='.$this->option('state') : '').($this->option('force') ? ' --force' : ''));

                return self::FAILURE;
            }
        }

        $this->components->info('Modules installed. Run: npm install && npm run build');

        // Last, so it isn't scrolled away. The patches only add traits and interfaces the
        // module needs at runtime, so nothing above depended on them.
        foreach ($conflicts as $patch) {
            $this->components->warn("{$patch} did not apply. Make its change by hand: see the module's README, \"Manual install\".");
        }

        // Read from the installed module itself, so it works for private modules too.
        $stack = $config->getFramework();

        foreach ($modules as $module) {
            $stacks = data_get(json_decode((string) @file_get_contents($module->path('composer.json')), true), 'extra.saucebase.frameworks');

            if ($stack !== null && is_array($stacks) && ! in_array($stack, $stacks, true)) {
                $this->components->warn("{$module->name} has no {$stack} frontend: its pages won't load.");
            }
        }

        return self::SUCCESS;
    }

    /**
     * What this run configures, checked before anything is written: each module's keys
     * from `extra.saucebase.configure.<module>` in the modules being set up (a template's),
     * then `--set` flags, which win. A key marked #[InstallSetting] is a setting, cast and
     * validated; any other key answers the module's install command, `--key=value`.
     *
     * @param  ModuleConfig[]  $modules
     * @return array<string, array{settings: InstallSettings, template: array<string, mixed>, flags: array<string, mixed>, answers: array<string, string|int|float|bool|null>, options: array<string, InputOption>}>|string
     */
    private function configuration(array $modules): array|string
    {
        $plan = [];

        foreach ($modules as $module) {
            $plan[$module->name] = ['settings' => InstallSettings::of($module), 'template' => [], 'flags' => [], 'answers' => [], 'options' => $this->installOptions($module)];
        }

        $entries = [];

        foreach ($modules as $module) {
            $declared = data_get(json_decode((string) @file_get_contents($module->path('composer.json')), true), 'extra.saucebase.configure', []);

            foreach ((array) $declared as $target => $values) {
                foreach ((array) $values as $key => $value) {
                    $entries[] = ['template', (string) $target, (string) $key, $value];
                }
            }
        }

        foreach ((array) $this->option('set') as $flag) {
            if (! preg_match('/^([a-z0-9-]+)\.([A-Za-z_][A-Za-z0-9_-]*)=(.*)$/', (string) $flag, $match)) {
                return "configure: --set takes module.key=value: {$flag}";
            }

            $entries[] = ['flags', $match[1], $match[2], $match[3]];
        }

        try {
            foreach ($entries as [$source, $target, $key, $value]) {
                $settings = $plan[$target]['settings'] ?? throw new \InvalidArgumentException("{$target} is not being installed here.");

                if ($settings->marks($key)) {
                    $plan[$target][$source][$key] = $settings->cast($key, $value);
                } elseif ($settings->declares($key)) {
                    throw new \InvalidArgumentException("{$target}.{$key} can't be set at install: it is not marked #[InstallSetting].");
                } elseif (! isset($plan[$target]['options'][$key])) {
                    throw new \InvalidArgumentException("{$target}.{$key} is neither an install setting nor an option of {$target}'s install command.");
                } elseif (! $plan[$target]['options'][$key]->acceptValue()) {
                    // A flag: on or off, whatever spelling the template or `--set` used.
                    $flag = is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $plan[$target]['answers'][$key] = $flag ?? throw new \InvalidArgumentException("{$target}.{$key} is a flag: true or false, not ".json_encode($value).'.');
                } elseif (! (is_scalar($value) || $value === null)
                    || (is_string($value) && ! preg_match('#^[A-Za-z0-9._:/@+-]*$#', $value))) {
                    // Option names and plain values only, so an answer can never become another command.
                    throw new \InvalidArgumentException("{$target}.{$key} answers the install command, so it takes an option name and a plain value: ".json_encode($value));
                } else {
                    $plan[$target]['answers'][$key] = $value;
                }
            }
        } catch (\InvalidArgumentException $e) {
            return 'configure: '.$e->getMessage();
        }

        return $plan;
    }

    /**
     * The options a module's install commands take: the only answers `configure` may give
     * it. Read from the commands themselves, so a typo fails before anything is written.
     *
     * @return array<string, InputOption>
     */
    private function installOptions(ModuleConfig $module): array
    {
        $options = [];
        $composer = json_decode((string) @file_get_contents($module->path('composer.json')), true);

        foreach ((array) data_get($composer, 'extra.saucebase.post-install', []) as $line) {
            $name = strtok(substr((string) $line, strlen('php artisan ')), ' ');

            if (str_starts_with((string) $line, 'php artisan ') && $name && $this->getApplication()?->has($name)) {
                $options += $this->getApplication()->find($name)->getNativeDefinition()->getOptions();
            }
        }

        return $options;
    }

    /**
     * Saves each module's settings: a flag beats the person's answer, which beats the
     * template's value. Asked only when somebody is there and wants to.
     *
     * @param  array<string, array{settings: InstallSettings, template: array<string, mixed>, flags: array<string, mixed>, answers: array<string, mixed>}>  $plan
     */
    private function applySettings(array $plan): void
    {
        foreach ($plan as $name => ['settings' => $settings, 'template' => $template, 'flags' => $flags]) {
            if ($settings->isEmpty()) {
                continue;
            }

            $answers = $this->input->isInteractive() && confirm(__('Configure :module?', ['module' => $name]), false)
                ? $settings->ask($template, array_keys($flags))
                : [];

            $settings->save([...$template, ...$answers, ...$flags]);
        }
    }

    /**
     * `--option=value` for each answer the line doesn't already give, a bare `--option` for
     * true; false and null are left out. The line's own options win.
     *
     * @param  array<string, string|int|float|bool|null>  $options
     */
    private function appendOptions(string $line, array $options): string
    {
        $appended = '';

        foreach ($options as $option => $value) {
            if ($value === false || $value === null || preg_match('/(^|\s)--'.preg_quote($option, '/').'(=|\s|$)/', $line)) {
                continue;
            }

            $appended .= $value === true ? " --{$option}" : " --{$option}={$value}";
        }

        return $appended;
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

        try {
            return $this->getApplication()->find((string) $input->getFirstArgument())->run($input, $this->output);
        } catch (ExceptionInterface $e) {
            // An answer the command doesn't take (a typo in `configure`): said, not thrown.
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
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
