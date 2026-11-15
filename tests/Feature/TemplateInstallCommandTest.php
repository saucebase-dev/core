<?php

namespace Saucebase\Core\Tests\Feature;

use Dotenv\Dotenv;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Saucebase\Core\Tests\TestCase;

/**
 * `saucebase:template-install` requires a template, copies its stubs over the
 * app (flattened to the app's framework), then sets everything up with a state.
 */
class TemplateInstallCommandTest extends TestCase
{
    private Filesystem $files;

    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->appPath = sys_get_temp_dir().'/saucebase-template-install-'.uniqid();
        $this->files->ensureDirectoryExists($this->appPath.'/routes');
        $this->files->put($this->appPath.'/composer.json', '{}');
        $this->files->put($this->appPath.'/composer.lock', $this->lock([]));
        $this->files->put($this->appPath.'/saucebase.json', '{"framework": "vue"}');
        $this->files->put($this->appPath.'/routes/web.php', 'core');
        $this->app->setBasePath($this->appPath);

        config(['saucebase.demo_password' => 'secret']);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->appPath);

        parent::tearDown();
    }

    public function test_stubs_are_copied_flattened_to_the_app_framework(): void
    {
        $this->template([
            'routes/web.php' => 'template',
            'resources/js/vue/pages/Index.vue' => 'vue',
            'resources/js/react/pages/Index.tsx' => 'react',
            'resources/css/theme.css' => 'css',
        ]);

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertSuccessful();

        $this->assertSame('template', $this->files->get($this->appPath.'/routes/web.php'));
        $this->assertSame('vue', $this->files->get($this->appPath.'/resources/js/pages/Index.vue'));
        $this->assertSame('css', $this->files->get($this->appPath.'/resources/css/theme.css'));
        $this->assertFileDoesNotExist($this->appPath.'/resources/js/pages/Index.tsx');
        $this->assertDirectoryDoesNotExist($this->appPath.'/resources/js/vue');
    }

    /** Any vendor's template: the full package name, its folder the name after the slash. */
    public function test_a_template_is_named_by_its_full_package_name(): void
    {
        $this->files->ensureDirectoryExists($this->appPath.'/modules/notes/stubs/routes');
        $this->files->put($this->appPath.'/modules/notes/stubs/routes/web.php', 'notes');
        Process::fake(function (PendingProcess $process) {
            if ($process->command[0] === 'composer') {
                $this->files->put($this->appPath.'/composer.lock', $this->lock(['acme/notes' => []], templates: ['acme/notes']));
            }

            return Process::result();
        });

        $this->artisan('saucebase:template-install', ['template' => 'acme/notes'])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command === ['composer', 'require', 'acme/notes', '--no-interaction']);
        $this->assertSame('notes', $this->files->get($this->appPath.'/routes/web.php'));
    }

    public function test_setup_runs_after_the_stubs_with_the_state_asked_for(): void
    {
        $this->template(['routes/web.php' => 'template'], defaultState: true, setup: function () {
            $this->assertSame('template', $this->files->get($this->appPath.'/routes/web.php'));
        });

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--state' => 'default'])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'saucebase:module-setup', 'auth', 'uptime', '--configure']);
        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'modules:seed', '--module=auth', '--module=uptime', '--state=default']);
    }

    /**
     * The state is seeded last, in a process of its own: after every module's install
     * command, so it sees the app as installed (tenancy's mode, for one, decides addresses).
     */
    public function test_the_state_is_seeded_after_setup(): void
    {
        $this->template([], defaultState: true, setup: function () {
            Process::assertDidntRun(fn (PendingProcess $p) => in_array('modules:seed', $p->command, true));
        });

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--state' => 'default'])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => in_array('modules:seed', $p->command, true));
    }

    /** Demo content is asked for, like `saucebase new --demo`: a real app would have to delete it. */
    public function test_without_a_state_the_template_installs_clean(): void
    {
        $this->template([], defaultState: true);

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'saucebase:module-setup', 'auth', 'uptime', '--configure']);
        Process::assertDidntRun(fn (PendingProcess $p) => in_array('modules:seed', $p->command, true));
    }

    /** Only seeded accounts use the password: a clean install needs none. */
    public function test_a_clean_install_needs_no_demo_password(): void
    {
        config(['saucebase.demo_password' => null]);
        $this->template([], defaultState: true);

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertSuccessful();
    }

    public function test_a_state_option_replaces_the_default(): void
    {
        $this->template([], defaultState: true);

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--state' => 'agency', '--force' => true])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'saucebase:module-setup', 'auth', 'uptime', '--force', '--configure']);
        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'modules:seed', '--module=auth', '--module=uptime', '--state=agency', '--force']);
    }

    public function test_install_settings_are_passed_on_to_the_setup(): void
    {
        $this->template([]);

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--set' => ['auth.registration_enabled=false']])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'saucebase:module-setup', 'auth', 'uptime', '--configure', '--set=auth.registration_enabled=false']);
    }

    public function test_a_template_without_a_default_state_is_set_up_without_one(): void
    {
        $this->template([]);

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command === [PHP_BINARY, 'artisan', 'saucebase:module-setup', 'auth', 'uptime', '--configure']);
        Process::assertDidntRun(fn (PendingProcess $p) => in_array('modules:seed', $p->command, true));
    }

    /** The seeding makes a missing DEMO_PASSWORD (modules:seed), so the install doesn't stop for it. */
    public function test_a_blank_demo_password_does_not_stop_the_install(): void
    {
        config(['saucebase.demo_password' => null]);
        Process::fake();

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--state' => 'default']);

        Process::assertRan(fn (PendingProcess $p) => $p->command[0] === 'composer');
    }

    public function test_a_contributor_checkout_is_refused(): void
    {
        $this->files->put($this->appPath.'/saucebase.json', '{"framework": "vue", "contributor": true}');
        Process::fake();

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertFailed();

        Process::assertNothingRan();
    }

    public function test_an_app_with_another_template_is_refused(): void
    {
        $this->files->put($this->appPath.'/composer.lock', $this->lock(['saucebase/crm' => []], templates: ['saucebase/crm']));
        Process::fake();

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertFailed();

        Process::assertNothingRan();
    }

    public function test_the_same_template_can_be_installed_again(): void
    {
        $this->files->put($this->appPath.'/composer.lock', $this->lock(['saucebase/uptime' => []], templates: ['saucebase/uptime']));
        $this->template(['routes/web.php' => 'template']);

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertSuccessful();

        $this->assertSame('template', $this->files->get($this->appPath.'/routes/web.php'));
    }

    /** Composer can fail after writing them (a plugin, a script): the app gets its files back. */
    public function test_a_failed_composer_require_puts_composer_files_back(): void
    {
        Process::fake(function (PendingProcess $process) {
            $this->files->put($this->appPath.'/composer.json', '{"require": {"saucebase/uptime": "*"}}');
            $this->files->put($this->appPath.'/composer.lock', $this->lock(['saucebase/uptime' => []], templates: ['saucebase/uptime']));

            return Process::result(exitCode: 1);
        });

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])
            ->expectsOutputToContain('composer install')
            ->assertFailed();

        $this->assertSame('{}', $this->files->get($this->appPath.'/composer.json'));
        $this->assertSame($this->lock([]), $this->files->get($this->appPath.'/composer.lock'));
    }

    public function test_a_package_that_is_not_a_template_is_refused(): void
    {
        $this->template(['routes/web.php' => 'template'], type: 'saucebase-module');

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertFailed();

        $this->assertSame('core', $this->files->get($this->appPath.'/routes/web.php'));
    }

    /** A quoted value over several lines is copied whole, so .env still parses. */
    public function test_a_multiline_env_value_is_copied_whole(): void
    {
        $this->files->put($this->appPath.'/.env', "APP_NAME=Acme\n");
        $this->template(['.env.example' => "UPTIME_KEY=\"-----BEGIN-----\nabc\n-----END-----\"\nUPTIME_TIMEOUT=10\n"]);

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--no-interaction' => true])->assertSuccessful();

        $env = Dotenv::parse($this->files->get($this->appPath.'/.env'));
        $this->assertSame("-----BEGIN-----\nabc\n-----END-----", $env['UPTIME_KEY']);
        $this->assertSame('10', $env['UPTIME_TIMEOUT']);
    }

    public function test_missing_env_keys_from_the_template_example_are_added_below_a_note(): void
    {
        $this->files->put($this->appPath.'/.env', "APP_NAME=Acme\nUPTIME_HISTORY_DAYS=7\n");
        $this->template(['.env.example' => "APP_NAME=Example\nUPTIME_HISTORY_DAYS=30\nUPTIME_TIMEOUT=10\n# A comment\nUPTIME_AGENT=\"Uptime bot\"\n"]);

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])
            ->expectsConfirmation('Add 2 missing keys from the uptime template to .env?', 'yes')
            ->assertSuccessful();

        $env = $this->files->get($this->appPath.'/.env');
        $this->assertStringStartsWith("APP_NAME=Acme\nUPTIME_HISTORY_DAYS=7\n", $env);
        $this->assertStringContainsString('# Added by saucebase:template-install (uptime)', $env);
        $this->assertStringEndsWith("UPTIME_TIMEOUT=10\nUPTIME_AGENT=\"Uptime bot\"\n", $env);
        $this->assertSame(1, substr_count($env, 'UPTIME_HISTORY_DAYS'));
        // The example itself reaches the app, as documentation.
        $this->assertFileExists($this->appPath.'/.env.example');
    }

    public function test_without_interaction_missing_env_keys_are_added(): void
    {
        $this->files->put($this->appPath.'/.env', "APP_NAME=Acme\n");
        $this->template(['.env.example' => "UPTIME_TIMEOUT=10\n"]);

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--no-interaction' => true])->assertSuccessful();

        $this->assertStringContainsString("UPTIME_TIMEOUT=10\n", $this->files->get($this->appPath.'/.env'));
    }

    public function test_declining_the_env_keys_warns_and_still_installs(): void
    {
        $this->files->put($this->appPath.'/.env', "APP_NAME=Acme\n");
        $this->template(['.env.example' => "UPTIME_TIMEOUT=10\n"]);

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])
            ->expectsConfirmation('Add 1 missing key from the uptime template to .env?', 'no')
            ->expectsOutputToContain('UPTIME_TIMEOUT')
            ->assertSuccessful();

        $this->assertSame("APP_NAME=Acme\n", $this->files->get($this->appPath.'/.env'));
        Process::assertRan(fn (PendingProcess $p) => in_array('saucebase:module-setup', $p->command, true));
    }

    public function test_a_second_run_adds_no_env_keys(): void
    {
        $this->files->put($this->appPath.'/.env', "APP_NAME=Acme\n");
        $this->template(['.env.example' => "UPTIME_TIMEOUT=10\n"]);

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--no-interaction' => true])->assertSuccessful();
        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertSuccessful();

        $this->assertSame(1, substr_count($this->files->get($this->appPath.'/.env'), 'UPTIME_TIMEOUT'));
    }

    /** @return array<string, array{array<string, string>}> */
    public static function invalidStubs(): array
    {
        return [
            'an env file' => [['routes/web.php' => 'template', '.env' => 'APP_KEY=x']],
            'a local env file' => [['routes/web.php' => 'template', '.env.local' => 'APP_KEY=x']],
            'composer.json' => [['routes/web.php' => 'template', 'composer.json' => '{}']],
            'two stubs for one file' => [['routes/web.php' => 'template', 'resources/js/vue/app.ts' => 'a', 'resources/js/app.ts' => 'b']],
            'no stubs for the framework' => [['routes/web.php' => 'template', 'resources/js/react/pages/Index.tsx' => 'react']],
        ];
    }

    /** @param  array<string, string>  $stubs */
    #[DataProvider('invalidStubs')]
    public function test_invalid_stubs_write_nothing(array $stubs): void
    {
        $this->template($stubs);

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertFailed();

        $this->assertSame('core', $this->files->get($this->appPath.'/routes/web.php'));
        Process::assertDidntRun(fn (PendingProcess $p) => in_array('saucebase:module-setup', $p->command, true));
    }

    public function test_a_symlinked_stub_writes_nothing(): void
    {
        $this->template(['routes/web.php' => 'template']);
        symlink('/etc/hosts', $this->appPath.'/modules/uptime/stubs/hosts');

        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertFailed();

        $this->assertSame('core', $this->files->get($this->appPath.'/routes/web.php'));
    }

    public function test_a_failed_setup_names_the_command_to_retry(): void
    {
        $this->template([], setupFails: true);

        $this->artisan('saucebase:template-install', ['template' => 'uptime', '--state' => 'agency'])
            ->expectsOutputToContain('php artisan saucebase:template-install uptime --state=agency')
            ->assertFailed();
    }

    /** On a retry Composer has nothing new to install; the template's modules are still set up. */
    public function test_a_retry_sets_up_the_templates_modules_again(): void
    {
        $this->template([], setupFails: true);
        $this->artisan('saucebase:template-install', ['template' => 'uptime']);

        // The retry: Composer changes nothing, setup succeeds.
        $setups = [];
        Process::fake(function (PendingProcess $process) use (&$setups) {
            in_array('saucebase:module-setup', $process->command, true) && $setups[] = $process->command;

            return Process::result();
        });
        $this->artisan('saucebase:template-install', ['template' => 'uptime'])->assertSuccessful();

        $this->assertSame([[PHP_BINARY, 'artisan', 'saucebase:module-setup', 'auth', 'uptime', '--configure']], $setups);
    }

    /**
     * Fakes Composer installing the template (with auth) into modules/uptime.
     *
     * @param  array<string, string>  $stubs  Path inside stubs/ => contents.
     */
    private function template(array $stubs, bool $defaultState = false, string $type = 'saucebase-template', ?\Closure $setup = null, bool $setupFails = false): void
    {
        Process::fake(function (PendingProcess $process) use ($stubs, $defaultState, $type, $setup, $setupFails) {
            if ($process->command[0] === 'composer') {
                $module = $this->appPath.'/modules/uptime';
                foreach ($stubs as $path => $contents) {
                    $this->files->ensureDirectoryExists(dirname("{$module}/stubs/{$path}"));
                    $this->files->put("{$module}/stubs/{$path}", $contents);
                }
                if ($defaultState) {
                    $this->files->ensureDirectoryExists("{$module}/database/seeders");
                    $this->files->put("{$module}/database/seeders/DefaultUptimeDatabaseSeeder.php", '<?php');
                }
                $this->files->put($this->appPath.'/composer.lock', $this->lock([
                    'saucebase/auth' => [],
                    'saucebase/uptime' => ['saucebase/auth'],
                ], templates: $type === 'saucebase-template' ? ['saucebase/uptime'] : []));
            }

            if (in_array('saucebase:module-setup', $process->command, true)) {
                $setup && $setup();

                return Process::result(exitCode: $setupFails ? 1 : 0);
            }

            return Process::result();
        });

        // The symlink test needs the folder before the command runs.
        $this->files->ensureDirectoryExists($this->appPath.'/modules/uptime/stubs');
    }

    /**
     * @param  array<string, list<string>>  $modules  name => the modules it requires.
     * @param  list<string>  $templates
     */
    private function lock(array $modules, array $templates = []): string
    {
        $packages = [];
        foreach ($modules as $name => $requires) {
            $packages[] = ['name' => $name, 'type' => in_array($name, $templates, true) ? 'saucebase-template' : 'saucebase-module', 'require' => array_fill_keys(['saucebase/core', ...$requires], '*')];
        }

        return (string) json_encode(['packages' => $packages]);
    }
}
