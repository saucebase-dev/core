<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Saucebase\Core\Tests\TestCase;

/**
 * `saucebase:module-install` requires the modules, then hands the rest to
 * `saucebase:module-setup` in a new process, which loads the modules just installed.
 */
class ModuleInstallCommandTest extends TestCase
{
    private Filesystem $files;

    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->appPath = sys_get_temp_dir().'/saucebase-module-install-'.uniqid();
        $this->files->ensureDirectoryExists($this->appPath);
        $this->files->put($this->appPath.'/composer.json', '{"require": {"saucebase/auth": "^1.0"}}');
        $this->app->setBasePath($this->appPath);

        Http::fake([
            'packagist.org/*' => Http::response(['packages' => [
                'saucebase/auth' => [],
                'saucebase/billing' => [],
                'saucebase/blog' => [],
                'saucebase/old' => ['abandoned' => true],
            ]]),
        ]);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->appPath);

        parent::tearDown();
    }

    public function test_every_module_is_required_in_one_composer_run_then_set_up_in_a_new_process(): void
    {
        Process::fake();
        $this->artisan('saucebase:module-install', ['modules' => ['billing', 'blog']])->assertSuccessful();

        Process::assertRanTimes(fn (PendingProcess $p) => $p->command[0] === 'composer', 1);
        Process::assertRan(fn (PendingProcess $p) => $p->command === ['composer', 'require', 'saucebase/billing', 'saucebase/blog', '--no-interaction']);
        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'saucebase:module-setup', 'billing', 'blog']);
    }

    public function test_demo_is_passed_on_to_the_setup(): void
    {
        Process::fake();
        config(['saucebase.demo_password' => 'secret']);
        $this->artisan('saucebase:module-install', ['modules' => ['blog'], '--demo' => true])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'saucebase:module-setup', 'blog', '--demo']);
    }

    public function test_demo_without_a_demo_password_fails_before_anything_runs(): void
    {
        Process::fake();
        config(['saucebase.demo_password' => null]);

        $this->artisan('saucebase:module-install', ['modules' => ['blog'], '--demo' => true])
            ->expectsOutputToContain('Set DEMO_PASSWORD in .env')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_a_failed_require_stops_before_the_setup(): void
    {
        Process::fake(["'composer' *" => Process::result(errorOutput: 'not found', exitCode: 1)]);

        $this->artisan('saucebase:module-install', ['modules' => ['blog']])->assertFailed();

        Process::assertDidntRun(fn (PendingProcess $p) => in_array('saucebase:module-setup', $p->command, true));
    }

    public function test_with_no_names_it_offers_the_modules_not_yet_installed(): void
    {
        Process::fake();
        $this->artisan('saucebase:module-install')
            ->expectsChoice('Which modules would you like to install?', ['blog'], [
                'billing' => 'Billing',
                'blog' => 'Blog',
            ])
            ->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command === ['composer', 'require', 'saucebase/blog', '--no-interaction']);
    }

    public function test_with_no_names_and_no_interaction_it_fails(): void
    {
        Process::fake();
        $this->artisan('saucebase:module-install', ['--no-interaction' => true])->assertFailed();

        Process::assertNothingRan();
    }
}
