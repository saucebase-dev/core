<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Modules\ModuleTestFixture\Console\Commands\InstallFixtureCommand;
use Modules\ModuleTestFixture\Database\Seeders\DatabaseSeeder;
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

    public function test_demo_also_seeds_demo_content(): void
    {
        Process::fake();
        config(['saucebase.demo_password' => 'secret']);

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture'], '--demo' => true])->assertSuccessful();

        $this->assertSame(['DatabaseSeeder', 'DemoModuleTestFixtureDatabaseSeeder'], DatabaseSeeder::$ran);
    }

    public function test_a_failed_step_fails_the_setup_and_stops_there(): void
    {
        Process::fake();
        config(['saucebase.demo_password' => null]); // modules:seed --demo refuses without it

        $this->artisan('saucebase:module-setup', ['modules' => ['module-test-fixture'], '--demo' => true])
            ->expectsOutputToContain('run this again: php artisan saucebase:module-setup module-test-fixture --demo')
            ->assertFailed();

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

    public function test_an_unknown_module_fails(): void
    {
        Process::fake();

        $this->artisan('saucebase:module-setup', ['modules' => ['missing']])->assertFailed();
    }
}
