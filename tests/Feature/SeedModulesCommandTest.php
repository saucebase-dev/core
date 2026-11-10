<?php

namespace Saucebase\Core\Tests\Feature;

use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use Mockery;
use Modules\ModuleTestFixture\Database\Seeders\DatabaseSeeder;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * `modules:seed` runs each module's required data, and with `--state=<name>` each
 * module's `{State}{Module}DatabaseSeeder` too — required data first, for every module,
 * so a state can refer to anything another module needs to exist.
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

    public function test_a_state_runs_its_seeders_after_the_required_ones(): void
    {
        $this->artisan('modules:seed', ['--state' => 'showcase'])->assertSuccessful();

        $this->assertSame(
            ['DatabaseSeeder', 'ShowcaseModuleTestFixtureDatabaseSeeder'],
            DatabaseSeeder::$ran,
        );
    }

    public function test_a_state_refuses_without_a_demo_password(): void
    {
        config(['saucebase.demo_password' => null]);

        $this->artisan('modules:seed', ['--state' => 'showcase'])
            ->expectsOutputToContain('Set DEMO_PASSWORD in .env')
            ->assertFailed();

        $this->assertSame([], DatabaseSeeder::$ran);
    }

    public function test_a_state_no_module_provides_fails_without_seeding_anything(): void
    {
        $this->artisan('modules:seed', ['--state' => 'missing'])
            ->expectsOutputToContain('No module has a "missing" state.')
            ->assertFailed();

        $this->assertSame([], DatabaseSeeder::$ran);
    }

    public function test_a_state_in_production_asks_first(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('modules:seed', ['--state' => 'showcase'])
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed()
            ->run();
        $this->app->detectEnvironment(fn () => 'testing'); // or teardown's rollback asks too

        $this->assertSame([], DatabaseSeeder::$ran);
    }

    public function test_required_data_in_production_asks_first(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('modules:seed')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed()
            ->run();
        $this->app->detectEnvironment(fn () => 'testing');

        $this->assertSame([], DatabaseSeeder::$ran);
    }

    public function test_a_state_in_production_runs_with_force(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('modules:seed', ['--state' => 'showcase', '--force' => true])->assertSuccessful()->run();
        $this->app->detectEnvironment(fn () => 'testing');

        $this->assertSame(
            ['DatabaseSeeder', 'ShowcaseModuleTestFixtureDatabaseSeeder'],
            DatabaseSeeder::$ran,
        );
    }

    public function test_module_option_matches_case_insensitively(): void
    {
        $this->artisan('modules:seed', ['--module' => 'Module-Test-Fixture', '--state' => 'showcase'])
            ->assertSuccessful();

        $this->assertSame(
            ['DatabaseSeeder', 'ShowcaseModuleTestFixtureDatabaseSeeder'],
            DatabaseSeeder::$ran,
        );
    }

    public function test_an_unknown_module_fails_without_seeding_anything(): void
    {
        $this->artisan('modules:seed', ['--module' => 'missing', '--state' => 'showcase'])
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

        $this->artisan('modules:seed', ['--state' => 'showcase'])->assertSuccessful();

        $this->assertSame(['omega', 'alpha', 'omega showcase', 'alpha showcase'], self::$ran);
    }

    public function test_a_failing_seeder_fails_the_command(): void
    {
        require_once dirname(__DIR__).'/Fixtures/seed-order/seeders.php';
        $registry = Mockery::mock($this->app->make(ModuleRegistry::class));
        $this->instance(ModuleRegistry::class, $registry);
        $registry->shouldReceive('modules')->andReturn(collect([
            'omega' => new ModuleConfig('omega', '/tmp/omega', collect()),
        ]));

        $this->expectExceptionMessage('broken seeder');

        $this->artisan('modules:seed', ['--state' => 'broken'])->run();
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

        $this->artisan('saucebase:module-setup', ['modules' => ['alpha', 'omega'], '--state' => 'showcase'])
            ->assertSuccessful();

        $this->assertSame(['omega', 'alpha', 'omega showcase', 'alpha showcase'], self::$ran);
    }
}
