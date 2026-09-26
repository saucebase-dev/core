<?php

namespace Saucebase\Core\Tests\Feature;

use Modules\ModuleTestFixture\Database\Seeders\DatabaseSeeder;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * `modules:seed` runs each module's required data, and with `--demo` its sample
 * content too — required data first, for every module, so demo content can refer to
 * anything another module needs to exist.
 */
class SeedModulesCommandTest extends TestCase
{
    use InteractsWithFixtureModules;

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseSeeder::$ran = [];
    }

    public function test_it_runs_only_the_required_seeder_by_default(): void
    {
        $this->artisan('modules:seed')->assertSuccessful();

        $this->assertSame(['DatabaseSeeder'], DatabaseSeeder::$ran);
    }

    public function test_demo_runs_the_demo_seeder_after_the_required_one(): void
    {
        $this->artisan('modules:seed', ['--demo' => true])->assertSuccessful();

        $this->assertSame(
            ['DatabaseSeeder', 'DemoModuleTestFixtureDatabaseSeeder'],
            DatabaseSeeder::$ran,
        );
    }

    public function test_module_option_matches_case_insensitively(): void
    {
        $this->artisan('modules:seed', ['--module' => 'Module-Test-Fixture', '--demo' => true])
            ->assertSuccessful();

        $this->assertSame(
            ['DatabaseSeeder', 'DemoModuleTestFixtureDatabaseSeeder'],
            DatabaseSeeder::$ran,
        );
    }

    public function test_an_unknown_module_fails_without_seeding_anything(): void
    {
        $this->artisan('modules:seed', ['--module' => 'missing', '--demo' => true])
            ->assertFailed();

        $this->assertSame([], DatabaseSeeder::$ran);
    }
}
