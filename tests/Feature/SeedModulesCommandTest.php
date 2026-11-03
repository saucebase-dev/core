<?php

namespace Saucebase\Core\Tests\Feature;

use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use Mockery;
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

    /** @var list<string> What the seed-order fixtures ran, in order. */
    public static array $ran = [];

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseSeeder::$ran = [];
        config(['saucebase.demo_password' => 'secret']);
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

    public function test_demo_refuses_without_a_demo_password(): void
    {
        config(['saucebase.demo_password' => null]);

        $this->artisan('modules:seed', ['--demo' => true])
            ->expectsOutputToContain('Set DEMO_PASSWORD in .env')
            ->assertFailed();

        $this->assertSame([], DatabaseSeeder::$ran);
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

    public function test_a_seeder_runs_after_the_installed_modules_it_names_in_seed_after(): void
    {
        require_once dirname(__DIR__).'/Fixtures/seed-order/seeders.php';
        self::$ran = [];
        // The real registry, with two stand-in modules in place of the fixture.
        $registry = Mockery::mock($this->app->make(ModuleRegistry::class));
        $this->instance(ModuleRegistry::class, $registry);
        $registry->shouldReceive('modules')->andReturn(collect([
            'alpha' => new ModuleConfig('alpha', '/tmp/alpha', collect()),
            'omega' => new ModuleConfig('omega', '/tmp/omega', collect()),
        ]));

        $this->artisan('modules:seed', ['--demo' => true])->assertSuccessful();

        $this->assertSame(['omega', 'alpha', 'omega demo', 'alpha demo'], self::$ran);
    }

    /** Setting up modules together seeds them as one group, or SeedAfter sees only one at a time. */
    public function test_module_setup_honours_seed_after_between_its_modules(): void
    {
        require_once dirname(__DIR__).'/Fixtures/seed-order/seeders.php';
        self::$ran = [];
        $modules = collect([
            'alpha' => new ModuleConfig('alpha', '/tmp/alpha', collect()),
            'omega' => new ModuleConfig('omega', '/tmp/omega', collect()),
        ]);
        $registry = Mockery::mock($this->app->make(ModuleRegistry::class));
        $this->instance(ModuleRegistry::class, $registry);
        $registry->shouldReceive('modules')->andReturn($modules);
        $registry->shouldReceive('module')->andReturnUsing(fn (string $name) => $modules[$name]);

        $this->artisan('saucebase:module-setup', ['modules' => ['alpha', 'omega'], '--demo' => true])
            ->assertSuccessful();

        $this->assertSame(['omega', 'alpha', 'omega demo', 'alpha demo'], self::$ran);
    }
}
