<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
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

        if (! Process::path(base_path())->forever()->run(['composer', 'require', ...$packages, '--no-interaction'], $output)->successful()) {
            $this->components->error('Composer could not install the modules. Nothing else was changed.');

            return self::FAILURE;
        }

        // TTY, so a module's own install command can ask its questions.
        return Process::path(base_path())->forever()->tty(SymfonyProcess::isTtySupported() && $this->input->isInteractive())
            ->run([PHP_BINARY, 'artisan', 'saucebase:module-setup', ...$modules, ...($this->option('demo') ? ['--demo'] : [])], $output)
            ->successful() ? self::SUCCESS : self::FAILURE;
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
