<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Modules\ModuleTestFixture\Database\Seeders\DatabaseSeeder;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * `modules:demo-accounts` lists the accounts a state's seeders declare with
 * `#[DemoAccount]`, without seeding anything. The installer reads it with `--json`.
 */
class DemoAccountsCommandTest extends TestCase
{
    use InteractsWithFixtureModules;

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseSeeder::$ran = [];
    }

    public function test_it_lists_every_account_the_state_declares_without_seeding(): void
    {
        $this->artisan('modules:demo-accounts', ['--state' => 'showcase'])
            ->expectsOutputToContain('fixture-admin@example.com')
            ->expectsOutputToContain('fixture-staff@example.com')
            ->assertSuccessful();

        $this->assertSame([], DatabaseSeeder::$ran);
    }

    public function test_json_gives_each_account_with_its_module_and_whether_it_is_featured(): void
    {
        Artisan::call('modules:demo-accounts', ['--state' => 'showcase', '--json' => true]);

        $this->assertSame([
            ['module' => 'module-test-fixture', 'email' => 'fixture-admin@example.com', 'description' => 'sees everything', 'featured' => true],
            ['module' => 'module-test-fixture', 'email' => 'fixture-staff@example.com', 'description' => 'staff', 'featured' => false],
        ], json_decode(Artisan::output(), true));
    }

    /** The installer asks after any demo; a state nobody declares accounts for is no error. */
    public function test_a_state_without_accounts_gives_an_empty_list(): void
    {
        Artisan::call('modules:demo-accounts', ['--state' => 'nothing-here', '--json' => true]);

        $this->assertSame([], json_decode(Artisan::output(), true));
    }
}
