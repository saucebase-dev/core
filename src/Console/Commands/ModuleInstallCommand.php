<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Saucebase\Core\Support\DependencyOrder;
use Symfony\Component\Process\Process as SymfonyProcess;

use function Laravel\Prompts\multiselect;

/**
 * Adds modules to an existing application: requires them, then runs
 * `saucebase:module-setup` in a new process, which is the first to load them.
 */
class ModuleInstallCommand extends Command
{
    protected $signature = 'saucebase:module-install
                            {modules?* : Module names (billing). Omit to pick from a list}
                            {--demo : Also seed demo content}';

    protected $description = 'Install Saucebase modules into this application';

    public function handle(): int
    {
        if ($this->option('demo') && blank(config('saucebase.demo_password'))) {
            $this->components->error('Set DEMO_PASSWORD in .env to seed demo data. Every demo account uses it.');

            return self::FAILURE;
        }

        $modules = $this->argument('modules') ?: ($this->input->isInteractive() ? $this->pick() : []);

        if ($modules === null) {
            $this->components->info('Every available module is already installed.');

            return self::SUCCESS;
        }

        if ($modules === []) {
            $this->components->error('Name the modules to install: php artisan saucebase:module-install billing');

            return self::FAILURE;
        }

        $output = fn (string $type, string $output) => $this->output->write($output);
        $packages = array_map(fn (string $name) => "saucebase/{$name}", $modules);

        $locked = $this->lockedModules();

        if (! Process::path(base_path())->forever()->run(['composer', 'require', ...$packages, '--no-interaction'], $output)->successful()) {
            $this->components->error('Composer could not install the modules. Nothing else was changed.');

            return self::FAILURE;
        }

        // A module another one requires (api-tokens needs auth) arrives with it, and each is
        // set up after the modules it requires, so their patches and tables exist first.
        $after = $this->lockedModules();
        $modules = DependencyOrder::sort([
            ...array_diff(array_keys($after), array_keys($locked)),
            ...$modules,
        ], $after);

        // TTY, so a module's own install command can ask its questions.
        return Process::path(base_path())->forever()->tty(SymfonyProcess::isTtySupported() && $this->input->isInteractive())
            ->run([PHP_BINARY, 'artisan', 'saucebase:module-setup', ...$modules, ...($this->option('demo') ? ['--demo'] : [])], $output)
            ->successful() ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, string[]> Each Saucebase module in composer.lock => the modules it requires. */
    private function lockedModules(): array
    {
        $packages = collect(json_decode((string) @file_get_contents(base_path('composer.lock')), true)['packages'] ?? [])
            ->where('type', 'saucebase-module');
        $names = $packages->pluck('name')->all();

        return $packages->mapWithKeys(fn (array $package): array => [
            Str::after($package['name'], '/') => collect(array_keys($package['require'] ?? []))
                ->intersect($names)
                ->map(fn (string $name): string => Str::after($name, '/'))
                ->values()
                ->all(),
        ])->all();
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
