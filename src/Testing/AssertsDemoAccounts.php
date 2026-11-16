<?php

namespace Saucebase\Core\Testing;

use Illuminate\Support\Str;
use ReflectionAttribute;
use ReflectionClass;
use Saucebase\Core\Attributes\DemoAccount;

/**
 * For a module's or template's tests: the accounts a state seeder declares with
 * `#[DemoAccount]` are the ones it creates, so the installer never names a login that
 * isn't there. Seeds the module's required data first, as `modules:seed` does.
 */
trait AssertsDemoAccounts
{
    /**
     * @param  class-string  $seeder
     * @param  bool  $seed  False when the test has seeded the state itself (a template's
     *                      seeder builds on other modules' data: `modules:seed --state=default`).
     */
    protected function assertDemoAccountsSeeded(string $seeder, bool $seed = true): void
    {
        $required = Str::beforeLast($seeder, '\\').'\\DatabaseSeeder';

        if ($seed) {
            $this->seed(array_values(array_filter([class_exists($required) ? $required : null, $seeder])));
        }

        $accounts = array_map(
            fn (ReflectionAttribute $attribute): DemoAccount => $attribute->newInstance(),
            (new ReflectionClass($seeder))->getAttributes(DemoAccount::class),
        );

        $this->assertNotEmpty($accounts, class_basename($seeder).' declares no #[DemoAccount].');

        foreach ($accounts as $account) {
            $this->assertDatabaseHas('users', ['email' => $account->email]);
        }
    }
}
