<?php

namespace Saucebase\Core\Console\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionClass;
use Saucebase\Core\Attributes\DemoAccount;

/** The seeders a module has for a state, by the `{State}{Module}DatabaseSeeder` name. */
trait FindsModuleSeeders
{
    /**
     * @param  Collection<int, mixed>  $modules
     * @param  callable(string): string  $seederName
     * @return Collection<array-key, class-string> Module name => seeder class, for those that have one.
     */
    protected function seeders(Collection $modules, callable $seederName): Collection
    {
        return $modules
            ->mapWithKeys(function ($module) use ($seederName): array {
                $studly = Str::studly($module->name);

                return [$module->name => "Modules\\{$studly}\\Database\\Seeders\\".$seederName($studly)];
            })
            ->filter(fn (string $seeder): bool => class_exists($seeder));
    }

    /**
     * @param  Collection<array-key, class-string>  $seeders  Module name => seeder class.
     * @return list<array{module: string, email: string, description: string, featured: bool}>
     */
    protected function demoAccounts(Collection $seeders): array
    {
        return $seeders
            ->flatMap(fn (string $seeder, string $module): array => array_map(
                function (ReflectionAttribute $attribute) use ($module): array {
                    $account = $attribute->newInstance();

                    return ['module' => $module, 'email' => $account->email, 'description' => $account->description, 'featured' => $account->featured];
                },
                (new ReflectionClass($seeder))->getAttributes(DemoAccount::class),
            ))
            ->values()
            ->all();
    }
}
