<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Modules\ModuleTestFixture\Console\Commands\InstallFixtureCommand;
use Modules\ModuleTestFixture\Database\Seeders\DatabaseSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * `saucebase:module-setup` wires installed modules into the app: patches, migrations,
 * seeders, then each module's own install command. The fixture module ships one patch.
 */
class ModuleSetupCommandTest extends TestCase
{
    use InteractsWithFixtureModules;

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseSeeder::$ran = [];
        InstallFixtureCommand::$ran = false;
        InstallFixtureCommand::$mode = null;
        InstallFixtureCommand::$options = [];
    }

    public function test_a_clean_patch_is_applied_then_the_module_is_migrated_and_seeded(): void
    {
        Process::fake(['*--reverse*' => Process::result(exitCode: 1), '*' => Process::result()]);

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture']])
            ->expectsOutputToContain('npm install && npm run build')
            ->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command[0] === 'git'
            && ! in_array('--reverse', $p->command, true)
            && str_ends_with(end($p->command), 'module-test-fixture/patches/user.patch'));
        $this->assertSame(['DatabaseSeeder'], DatabaseSeeder::$ran);
        $this->assertTrue(InstallFixtureCommand::$ran, 'The module\'s post-install command runs last.');
        $this->assertSame('path', InstallFixtureCommand::$mode, 'Its options are passed on.');
    }

    public function test_a_patch_already_in_place_is_skipped(): void
    {
        Process::fake();

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture']])->assertSuccessful();

        Process::assertRanTimes(fn (PendingProcess $p) => $p->command[0] === 'git', 1);
    }

    public function test_a_conflicting_patch_is_left_for_the_user_and_everything_else_still_runs(): void
    {
        Process::fake(['*' => Process::result(exitCode: 1)]);

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture']])
            ->expectsOutputToContain('module-test-fixture/patches/user.patch did not apply. Make its change by hand: see the module\'s README, "Manual install".')
            ->assertSuccessful();

        $this->assertSame(['DatabaseSeeder'], DatabaseSeeder::$ran);
        $this->assertTrue(InstallFixtureCommand::$ran);
    }

    public function test_a_patch_whose_lines_are_all_in_place_counts_as_applied(): void
    {
        // A formatter reorders what the patch added, so git no longer recognises it.
        $this->assertSetupWarns("<?php // fixture\n", warns: false);
    }

    public function test_an_added_line_that_only_appears_inside_another_line_does_not_count(): void
    {
        $this->assertSetupWarns("// <?php // fixture\n", warns: true);
    }

    private function assertSetupWarns(string $user, bool $warns): void
    {
        Process::fake(['*' => Process::result(exitCode: 1)]);
        $appPath = sys_get_temp_dir().'/saucebase-module-setup-'.uniqid();
        mkdir($appPath.'/app/Models', recursive: true);
        file_put_contents($appPath.'/app/Models/User.php', $user);
        $this->app->setBasePath($appPath);

        try {
            $command = $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture']]);
            $warns ? $command->expectsOutputToContain('did not apply') : $command->doesntExpectOutputToContain('did not apply');
            $command->assertSuccessful()->run();
        } finally {
            (new Filesystem)->deleteDirectory($appPath);
        }
    }

    public function test_a_state_also_seeds_its_content(): void
    {
        Process::fake();
        config(['saucebase.demo_password' => 'secret']);

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture'], '--state' => 'showcase'])->assertSuccessful();

        $this->assertSame(['DatabaseSeeder', 'ShowcaseModuleTestFixtureDatabaseSeeder'], DatabaseSeeder::$ran);
    }

    public function test_in_production_seeding_asks_first(): void
    {
        Process::fake();
        config(['saucebase.demo_password' => 'secret']);
        $this->app->detectEnvironment(fn () => 'production');

        // migrate is forced; seeding a state is the operator's call.
        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture'], '--state' => 'showcase'])
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed()
            ->run();
        $this->app->detectEnvironment(fn () => 'testing');

        $this->assertSame([], DatabaseSeeder::$ran);
    }

    public function test_in_production_force_seeds_without_asking(): void
    {
        Process::fake();
        config(['saucebase.demo_password' => 'secret']);
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture'], '--state' => 'showcase', '--force' => true])
            ->assertSuccessful()
            ->run();
        $this->app->detectEnvironment(fn () => 'testing');

        $this->assertSame(['DatabaseSeeder', 'ShowcaseModuleTestFixtureDatabaseSeeder'], DatabaseSeeder::$ran);
    }

    public function test_a_failed_step_fails_the_setup_and_stops_there(): void
    {
        Process::fake();
        config(['saucebase.demo_password' => null]); // modules:seed --state refuses without it

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture'], '--state' => 'showcase'])
            ->expectsOutputToContain('run this again: php artisan saucebase:module-setup module-test-fixture --state=showcase')
            ->assertFailed();

        $this->assertFalse(InstallFixtureCommand::$ran);
    }

    /**
     * A template answers another module's install questions: keys under `configure` in
     * its composer.json that aren't install settings become that module's install options.
     *
     * @param  array<string, mixed>  $options
     */
    private function withModuleOptions(array $options, \Closure $run): void
    {
        $composer = __DIR__.'/../Fixtures/modules/module-order-fixture/composer.json';
        $original = file_get_contents($composer);
        $json = json_decode($original, true);
        $json['extra']['saucebase']['configure'] = ['module-test-fixture' => $options];
        file_put_contents($composer, json_encode($json));

        try {
            $run();
        } finally {
            file_put_contents($composer, $original);
        }
    }

    public function test_configure_answers_another_modules_install_command(): void
    {
        Process::fake(['*--reverse*' => Process::result(exitCode: 1), '*' => Process::result()]);

        $this->withModuleOptions(['onboarding' => 'create', 'single' => true, 'mode' => 'subdomain'], function (): void {
            $this->artisan('saucebase:module-setup', ['modules' => ['module-order-fixture', 'module-test-fixture']])->assertSuccessful();
        });

        $this->assertSame('create', InstallFixtureCommand::$options['onboarding']);
        $this->assertTrue(InstallFixtureCommand::$options['single']);
        $this->assertSame('path', InstallFixtureCommand::$mode, 'The module\'s own line wins.');
    }

    public function test_false_and_null_options_are_left_out(): void
    {
        Process::fake(['*--reverse*' => Process::result(exitCode: 1), '*' => Process::result()]);

        $this->withModuleOptions(['single' => false, 'onboarding' => null], function (): void {
            $this->artisan('saucebase:module-setup', ['modules' => ['module-order-fixture', 'module-test-fixture']])->assertSuccessful();
        });

        $this->assertFalse(InstallFixtureCommand::$options['single']);
        $this->assertNull(InstallFixtureCommand::$options['onboarding']);
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function invalidInstallAnswers(): array
    {
        return [
            'a key that is not an option name' => [['--mode' => 'x']],
            'a key with a space' => [['mode x' => 'y']],
            'an array value' => [['mode' => ['a', 'b']]],
            'a value with a space' => [['onboarding' => 'create --single']],
            'a value with a quote' => [['onboarding' => "create'"]],
        ];
    }

    /** @param  array<string, mixed>  $options */
    #[DataProvider('invalidInstallAnswers')]
    public function test_invalid_install_answers_fail_before_anything_runs(array $options): void
    {
        Process::fake();

        $this->withModuleOptions($options, function (): void {
            $this->artisan('saucebase:module-setup', ['modules' => ['module-order-fixture', 'module-test-fixture']])
                ->expectsOutputToContain('configure')
                ->assertFailed();
        });

        $this->assertSame([], DatabaseSeeder::$ran);
        $this->assertFalse(InstallFixtureCommand::$ran);
    }

    public function test_a_post_install_entry_that_is_not_an_artisan_command_fails(): void
    {
        Process::fake();
        $composer = __DIR__.'/../Fixtures/modules/module-test-fixture/composer.json';
        $original = file_get_contents($composer);
        file_put_contents($composer, str_replace('php artisan module-test-fixture:install --mode=path', 'rm -rf storage', $original));

        try {
            $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture']])
                ->expectsOutputToContain('post-install runs artisan commands only: rm -rf storage')
                ->assertFailed();
        } finally {
            file_put_contents($composer, $original);
        }
    }

    public function test_a_module_with_a_taskfile_is_included_in_the_app_taskfile_once(): void
    {
        $taskfile = $this->taskfile("includes:\n    # ── BEGIN MODULES\n    # ── END MODULES\n");

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture']])->assertSuccessful();
        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture']])->assertSuccessful();

        $this->assertSame(
            "includes:\n    # ── BEGIN MODULES\n    module-test-fixture:\n        taskfile: ./modules/module-test-fixture/Taskfile.yml\n        optional: true\n    # ── END MODULES\n",
            file_get_contents($taskfile),
        );
    }

    public function test_a_module_without_a_taskfile_is_not_included(): void
    {
        $taskfile = $this->taskfile($content = "includes:\n    # ── BEGIN MODULES\n    # ── END MODULES\n");

        $this->artisan('saucebase:module-setup', ['modules' => ['module-order-fixture']])->assertSuccessful();

        $this->assertSame($content, file_get_contents($taskfile));
    }

    public function test_a_taskfile_without_the_markers_is_left_alone(): void
    {
        $taskfile = $this->taskfile($content = "includes: {}\n");

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture']])->assertSuccessful();

        $this->assertSame($content, file_get_contents($taskfile));
    }

    /** An app holding only a Taskfile; returns its path. */
    private function taskfile(string $content): string
    {
        Process::fake();
        $appPath = sys_get_temp_dir().'/saucebase-module-taskfile-'.uniqid();
        mkdir($appPath);
        file_put_contents($appPath.'/Taskfile.yml', $content);
        $this->app->setBasePath($appPath);
        $this->beforeApplicationDestroyed(fn () => (new Filesystem)->deleteDirectory($appPath));

        return $appPath.'/Taskfile.yml';
    }

    public function test_a_module_without_a_frontend_for_the_app_stack_is_flagged(): void
    {
        $this->taskfile('');
        file_put_contents(base_path('saucebase.json'), '{"framework": "vue"}');

        $this->artisan('saucebase:module-setup', ['modules' => ['module-order-fixture', 'module-test-fixture']])
            ->expectsOutputToContain("module-order-fixture has no vue frontend: its pages won't load.")
            ->doesntExpectOutputToContain('module-test-fixture has no')
            ->assertSuccessful();
    }

    public function test_an_unknown_module_fails(): void
    {
        Process::fake();

        $this->artisan('saucebase:module-setup', ['modules' => ['missing']])->assertFailed();
    }
}
