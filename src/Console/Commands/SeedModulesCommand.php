<?php

namespace Saucebase\Core\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InterNACHI\Modular\Support\ModuleRegistry;
use ReflectionAttribute;
use ReflectionClass;
use Saucebase\Core\Attributes\SeedAfter;
use Saucebase\Core\Support\DependencyOrder;

class SeedModulesCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'modules:seed
        {--module=* : Seed specific modules (case-insensitive), together. Omit to seed all.}
        {--state= : Also run each module\'s seeder for this state, e.g. showcase runs ShowcaseBlogDatabaseSeeder}
        {--force : Seed in production without asking}';

    protected $description = 'Seed all installed modules that have a DatabaseSeeder';

    public function handle(ModuleRegistry $registry): int
    {
        $state = $this->option('state') ? Str::studly($this->option('state')) : null;

        // Asked once here rather than by db:seed for every module. A state creates sample
        // accounts and content: never in production by accident.
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        if ($state !== null && blank(config('saucebase.demo_password'))) {
            $this->makeDemoPassword();
        }

        $modules = $registry->modules();

        // A string when another command calls this with one name.
        $names = (array) $this->option('module');

        if ($names !== []) {
            $named = collect();

            foreach ($names as $name) {
                $filter = strtolower($name);
                $module = $modules->first(fn ($m) => strtolower($m->name) === $filter);

                if ($module === null) {
                    $this->components->error("Module \"{$filter}\" not found.");

                    return self::FAILURE;
                }

                $named->push($module);
            }

            $modules = $named;
        }

        $required = $this->seeders($modules, fn (string $studly) => 'DatabaseSeeder');
        $stated = $state === null ? collect() : $this->seeders($modules, fn (string $studly) => "{$state}{$studly}DatabaseSeeder");

        if ($state !== null && $stated->isEmpty()) {
            $this->components->error("No module has a \"{$this->option('state')}\" state.");

            return self::FAILURE;
        }

        // Every module's required data exists before any state refers to it.
        return $this->seed($required) && $this->seed($stated) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Every account a state creates signs in with DEMO_PASSWORD. Without one, a random one
     * (as the installer makes) is saved in .env and shown, never a guessable default.
     */
    private function makeDemoPassword(): void
    {
        $env = $this->laravel->environmentFilePath();
        $saved = is_file($env) ? (Dotenv::parse((string) file_get_contents($env))['DEMO_PASSWORD'] ?? '') : '';

        // Set in .env but hidden by a cached config: use it, or every run would replace it.
        // Laravel reads these spellings as unset (Env::get), so they are not a password.
        if (! in_array(strtolower(trim($saved)), ['', 'null', '(null)', 'empty', '(empty)'], true)) {
            config(['saucebase.demo_password' => $saved]);

            return;
        }

        $password = Str::password(10, symbols: false);
        config(['saucebase.demo_password' => $password]);

        if (! is_file($env)) {
            $this->components->warn("No .env file: the demo accounts use the password {$password} for this run, not saved. Set DEMO_PASSWORD to keep one.");

            return;
        }

        $contents = (string) file_get_contents($env);
        $line = "DEMO_PASSWORD={$password}";
        // The whole value: a quoted one may span lines.
        $assignment = '/^DEMO_PASSWORD=(?:"(?:\\\\.|[^"\\\\])*"|\'[^\']*\'|[^\n]*)/m';
        $contents = preg_match($assignment, $contents)
            ? preg_replace($assignment, $line, $contents)
            : rtrim($contents, "\n").($contents === '' ? '' : "\n")."{$line}\n";

        file_put_contents($env, $contents);

        $this->components->warn("DEMO_PASSWORD was not set. The demo accounts use {$password}, now saved in .env.");
    }

    /**
     * @param  Collection<int, mixed>  $modules
     * @param  callable(string): string  $seederName
     * @return Collection<array-key, class-string> Module name => seeder class, for those that have one.
     */
    private function seeders(Collection $modules, callable $seederName): Collection
    {
        return $modules
            ->mapWithKeys(function ($module) use ($seederName): array {
                $studly = Str::studly($module->name);

                return [$module->name => "Modules\\{$studly}\\Database\\Seeders\\".$seederName($studly)];
            })
            ->filter(fn (string $seeder): bool => class_exists($seeder));
    }

    /** @param  Collection<array-key, class-string>  $seeders */
    private function seed(Collection $seeders): bool
    {
        $after = $seeders->map(
            fn (string $seeder): array => collect((new ReflectionClass($seeder))
                ->getAttributes(SeedAfter::class))
                ->flatMap(fn (ReflectionAttribute $attribute): array => $attribute->newInstance()->modules)
                ->all()
        );

        foreach (DependencyOrder::sort($seeders->keys()->all(), $after->all()) as $name) {
            $seeder = $seeders[$name];

            $seeded = false;

            $this->components->task(
                "{$name} ".class_basename($seeder),
                function () use ($seeder, &$seeded): bool {
                    // Asked once above; db:seed would ask again for every module.
                    return $seeded = $this->call('db:seed', ['--class' => $seeder, '--force' => true]) === self::SUCCESS;
                },
            );

            // The modules after this one may build on its data.
            if (! $seeded) {
                return false;
            }
        }

        return true;
    }
}
