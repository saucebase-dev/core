<?php

namespace Saucebase\Core\Tests\Feature;

use Dotenv\Dotenv;
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

    /** No DEMO_PASSWORD: one is made, saved in .env, and shown, so the demo accounts can be used. */
    /** Seeding by hand ends the way the installer does: with the account to sign in with. */
    public function test_a_state_names_its_featured_accounts_to_sign_in_with(): void
    {
        $this->artisan('modules:seed', ['--state' => 'showcase'])
            ->expectsOutputToContain('fixture-admin@example.com')
            ->doesntExpectOutputToContain('fixture-staff@example.com')
            ->assertSuccessful();
    }

    public function test_a_state_without_a_demo_password_makes_one_and_saves_it(): void
    {
        config(['saucebase.demo_password' => null]);
        $env = $this->envFile("APP_NAME=Acme\n");

        $this->artisan('modules:seed', ['--state' => 'showcase'])
            ->expectsOutputToContain('DEMO_PASSWORD')
            ->assertSuccessful();

        $password = Dotenv::parse(file_get_contents($env))['DEMO_PASSWORD'] ?? '';
        $this->assertSame(10, strlen($password));
        $this->assertSame($password, config('saucebase.demo_password'));
        $this->assertSame('Acme', Dotenv::parse(file_get_contents($env))['APP_NAME']);
        $this->assertSame(['DatabaseSeeder', 'ShowcaseModuleTestFixtureDatabaseSeeder'], DatabaseSeeder::$ran);
    }

    public function test_a_demo_password_already_set_is_kept(): void
    {
        $env = $this->envFile("DEMO_PASSWORD=secret\n");

        $this->artisan('modules:seed', ['--state' => 'showcase'])->assertSuccessful();

        $this->assertSame("DEMO_PASSWORD=secret\n", file_get_contents($env));
        $this->assertSame('secret', config('saucebase.demo_password'));
    }

    /** A cached config hides .env: the password saved there is used, not replaced. */
    public function test_a_password_in_env_wins_over_a_blank_cached_config(): void
    {
        config(['saucebase.demo_password' => null]);
        $env = $this->envFile("DEMO_PASSWORD=saved\n");

        $this->artisan('modules:seed', ['--state' => 'showcase'])->assertSuccessful();

        $this->assertSame("DEMO_PASSWORD=saved\n", file_get_contents($env));
        $this->assertSame('saved', config('saucebase.demo_password'));
    }

    /** Laravel reads these as unset, so they get a random password, never the literal word. */
    public function test_an_env_value_laravel_reads_as_empty_gets_a_random_password(): void
    {
        foreach (['null', '(null)', 'empty', '(empty)', '"  "'] as $value) {
            config(['saucebase.demo_password' => null]);
            $env = $this->envFile("DEMO_PASSWORD={$value}\n");

            $this->artisan('modules:seed', ['--state' => 'showcase'])->assertSuccessful();

            $password = Dotenv::parse(file_get_contents($env))['DEMO_PASSWORD'];
            $this->assertSame(10, strlen($password), "DEMO_PASSWORD={$value}");
            $this->assertSame($password, config('saucebase.demo_password'));
        }
    }

    /** A blank quoted value over several lines is replaced whole, so .env still parses. */
    public function test_a_blank_multiline_value_is_replaced_whole(): void
    {
        config(['saucebase.demo_password' => null]);
        $env = $this->envFile("DEMO_PASSWORD=\"\n\"\nAPP_NAME=Acme\n");

        $this->artisan('modules:seed', ['--state' => 'showcase'])->assertSuccessful();

        $parsed = Dotenv::parse(file_get_contents($env));
        $this->assertSame(10, strlen($parsed['DEMO_PASSWORD']));
        $this->assertSame('Acme', $parsed['APP_NAME']);
    }

    public function test_every_blank_assignment_gets_the_saved_password(): void
    {
        config(['saucebase.demo_password' => null]);
        $env = $this->envFile("DEMO_PASSWORD=\nAPP_NAME=Acme\nDEMO_PASSWORD=\n");

        $this->artisan('modules:seed', ['--state' => 'showcase'])->assertSuccessful();

        $this->assertSame(config('saucebase.demo_password'), Dotenv::parse(file_get_contents($env))['DEMO_PASSWORD']);
    }

    /** Env vars without a file (CI, containers): the password works for this run, and says it wasn't saved. */
    public function test_without_an_env_file_the_made_password_is_used_and_not_saved(): void
    {
        config(['saucebase.demo_password' => null]);
        $env = $this->envFile(null);

        $this->artisan('modules:seed', ['--state' => 'showcase'])
            ->expectsOutputToContain('not saved')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($env);
        $this->assertSame(10, strlen((string) config('saucebase.demo_password')));
    }

    public function test_required_data_alone_makes_no_password(): void
    {
        config(['saucebase.demo_password' => null]);
        $env = $this->envFile("APP_NAME=Acme\n");

        $this->artisan('modules:seed')->assertSuccessful();

        $this->assertSame("APP_NAME=Acme\n", file_get_contents($env));
    }

    /** The app's .env in a directory of its own, or no .env when null. */
    private function envFile(?string $contents): string
    {
        $dir = sys_get_temp_dir().'/seed-env-'.uniqid();
        mkdir($dir);
        $this->app->useEnvironmentPath($dir);

        if ($contents !== null) {
            file_put_contents("{$dir}/.env", $contents);
        }

        return "{$dir}/.env";
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
