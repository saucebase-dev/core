<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InterNACHI\Modular\Support\ModuleRegistry;

class SeedModulesCommand extends Command
{
    protected $signature = 'modules:seed
        {--module= : Seed a specific module (case-insensitive). Omit to seed all.}
        {--demo : Also run each module\'s Demo{Module}DatabaseSeeder with sample content.}';

    protected $description = 'Seed all installed modules that have a DatabaseSeeder';

    public function handle(ModuleRegistry $registry): int
    {
        $filter = $this->option('module') !== null
            ? strtolower($this->option('module'))
            : null;

        if ($filter !== null) {
            $module = $registry->modules()->first(fn ($m) => strtolower($m->name) === $filter);

            if ($module === null) {
                $this->components->error("Module \"{$filter}\" not found.");

                return self::FAILURE;
            }

            $modules = collect([$module]);
        } else {
            $modules = $registry->modules();
        }

        $this->seed($modules, fn (string $studly) => 'DatabaseSeeder');

        if ($this->option('demo')) {
            // Every module's required data exists before any demo content refers to it.
            $this->seed($modules, fn (string $studly) => "Demo{$studly}DatabaseSeeder");
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, mixed>  $modules
     * @param  callable(string): string  $seederName
     */
    private function seed(Collection $modules, callable $seederName): void
    {
        foreach ($modules as $module) {
            $studly = Str::studly($module->name);
            $seeder = "Modules\\{$studly}\\Database\\Seeders\\".$seederName($studly);

            if (! class_exists($seeder)) {
                continue;
            }

            $this->components->task(
                "{$module->name} ".class_basename($seeder),
                fn () => $this->call('db:seed', ['--class' => $seeder]) === self::SUCCESS,
            );
        }
    }
}
