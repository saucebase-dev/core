<?php

namespace Saucebase\Core\Tests\Feature;

use Database\Factories\UserFactory;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use Saucebase\Core\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    private Filesystem $files;

    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->appPath = sys_get_temp_dir().'/saucebase-install-test-'.uniqid();

        // The files of a new Laravel application that the install touches.
        $this->files->ensureDirectoryExists($this->appPath.'/bootstrap');
        $this->files->ensureDirectoryExists($this->appPath.'/routes');
        $this->files->put($this->appPath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");
        $this->files->put($this->appPath.'/routes/web.php', "<?php\n\n// welcome\n");

        $this->app->setBasePath($this->appPath);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->appPath);
        putenv('COLUMNS');

        parent::tearDown();
    }

    public function test_it_publishes_the_files_the_application_owns(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $this->assertStringContainsString('IndexController', $this->read('routes/web.php'));
        $this->assertStringContainsString('HasRoles', $this->read('app/Models/User.php'));
        $this->assertStringContainsString('RolesDatabaseSeeder', $this->read('database/seeders/DatabaseSeeder.php'));
        $this->assertFileExists($this->appPath.'/app/Http/Controllers/DashboardController.php');
        $this->assertFileExists($this->appPath.'/routes/navigation.php');
        $this->assertFileExists($this->appPath.'/public/site.webmanifest');
        $this->assertFileExists($this->appPath.'/public/images/icon-on-light.svg');
    }

    public function test_it_replaces_laravels_readme_with_one_for_saucebase(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $readme = $this->read('README.md');
        $this->assertStringContainsString('php artisan saucebase:module-install', $readme);
        $this->assertStringContainsString('docker compose exec app php artisan', $readme);
        $this->assertStringContainsString('resets the database', $readme);
    }

    public function test_the_published_user_factory_never_repeats_an_email_across_requests(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();
        require_once $this->appPath.'/database/factories/UserFactory.php';

        // E2E tests create each user in its own request, and each request starts a new
        // Faker whose unique() remembers nothing: seeded alike, they draw the same email.
        $emails = [];
        foreach ([1, 2] as $request) {
            fake()->seed(1);
            fake()->unique(reset: true);
            $emails[] = (new UserFactory)->definition()['email'];
        }

        $this->assertCount(2, array_unique($emails));
    }

    public function test_it_publishes_a_taskfile_with_an_empty_app_prefix(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        // The installer's Docker driver fills the prefix in; every other setup runs commands directly.
        $this->assertStringContainsString("APP: ''", $this->read('Taskfile.yml'));
    }

    public function test_the_published_example_test_migrates_the_database(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        // Laravel's own example test requests the home page, which now reads the settings table.
        $this->assertStringContainsString('use RefreshDatabase;', $this->read('tests/Feature/ExampleTest.php'));
    }

    public function test_it_publishes_what_module_tests_build_on(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        // A module's tests extend the application's test case and expect the seeded roles.
        $this->assertStringContainsString('protected $seed = true;', $this->read('tests/TestCase.php'));
        $this->assertStringContainsString('function createUser()', $this->read('tests/TestCase.php'));

        // A module's browser tests import the fixtures, which call these classes by name.
        $this->assertFileExists($this->appPath.'/playwright.config.ts');
        $this->assertFileExists($this->appPath.'/tests/e2e/fixtures/index.ts');
        $this->assertFileExists($this->appPath.'/tests/e2e/helpers/auth.ts');
        $this->assertFileExists($this->appPath.'/tests/e2e/helpers/modules.ts');
        $this->assertFileExists($this->appPath.'/tests/e2e/database.setup.ts');
        $this->assertFileExists($this->appPath.'/tests/e2e/assets.setup.ts');
        $this->assertFileExists($this->appPath.'/tests/Support/TestFixtures.php');
        $this->assertFileExists($this->appPath.'/tests/Support/AuthHelper.php');
        $this->assertFileExists($this->appPath.'/tests/Support/ModuleSupport.php');
    }

    public function test_it_publishes_the_test_configuration(): void
    {
        $this->files->put($this->appPath.'/phpunit.xml', "<phpunit>\n</phpunit>\n");

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $phpunit = $this->read('phpunit.xml');

        // The larger modules run out of PHP's default 128 MB.
        $this->assertStringContainsString('<ini name="memory_limit" value="2048M"/>', $phpunit);
        $this->assertStringContainsString('./modules/*/tests', $phpunit);

        // A container sets these for the running application, and PHPUnit keeps a variable that
        // already exists: unforced, the tests would run against the application's own database.
        // Laravel reads $_SERVER before $_ENV, so both are forced.
        foreach (['APP_ENV', 'DB_CONNECTION', 'DB_DATABASE', 'QUEUE_CONNECTION', 'SESSION_DRIVER'] as $name) {
            $this->assertMatchesRegularExpression('/<env name="'.$name.'" value="[^"]*" force="true"\/>/', $phpunit, $name);
            $this->assertMatchesRegularExpression('/<server name="'.$name.'" value="[^"]*" force="true"\/>/', $phpunit, $name);
        }
    }

    public function test_it_publishes_the_admin_panel_assets_and_keeps_them_current(): void
    {
        $this->files->put($this->appPath.'/composer.json', json_encode([
            'name' => 'acme/app',
            'extra' => ['laravel' => ['dont-discover' => []]],
            'scripts' => ['post-autoload-dump' => ['@php artisan package:discover --ansi']],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        // Without its published assets the admin panel renders unstyled and its scripts 404.
        $this->assertDirectoryExists($this->appPath.'/public/js/filament');

        // After every Composer run: a Filament update brings new assets, a new module brings types to generate.
        $composer = json_decode($this->read('composer.json'), true);
        $this->assertSame([
            '@php artisan package:discover --ansi',
            '@php artisan filament:upgrade',
            '@php artisan module:generate-types --all --no-interaction --ansi',
            '@php artisan modules:boost --ansi',
        ], $composer['scripts']['post-autoload-dump']);
        $this->assertSame('acme/app', $composer['name']);
        $this->assertStringContainsString('"dont-discover": []', $this->read('composer.json'));
    }

    public function test_it_lets_the_application_hold_modules_of_its_own(): void
    {
        $this->files->put($this->appPath.'/composer.json', json_encode(['name' => 'acme/app']));

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        // Without it Composer cannot find a module that only exists in modules/. Not canonical,
        // so a module that came from a registry still gets its updates from there.
        $this->assertSame([[
            'type' => 'path',
            'url' => 'modules/*',
            'canonical' => false,
            'options' => ['symlink' => true],
        ]], json_decode($this->read('composer.json'), true)['repositories']);

        // Composer refuses the repository while the folder it points into does not exist.
        $this->assertDirectoryExists($this->appPath.'/modules');
    }

    public function test_it_keeps_the_repositories_the_application_already_has(): void
    {
        $existing = ['type' => 'path', 'url' => 'modules/*', 'options' => ['symlink' => true]];
        $this->files->put($this->appPath.'/composer.json', json_encode(['name' => 'acme/app', 'repositories' => [$existing]]));

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $this->assertSame([$existing], json_decode($this->read('composer.json'), true)['repositories']);
    }

    public function test_it_registers_the_admin_panel(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $this->assertFileExists($this->appPath.'/app/Providers/Filament/AdminPanelProvider.php');
        $this->assertStringContainsString('AppServiceProvider::class', $this->read('bootstrap/providers.php'));
        $this->assertStringContainsString('AdminPanelProvider::class', $this->read('bootstrap/providers.php'));
    }

    #[DataProvider('frameworks')]
    public function test_it_publishes_the_chosen_framework(string $framework, string $entry): void
    {
        $this->artisan('saucebase:install', ['framework' => $framework, '--force' => true])->assertSuccessful();

        $this->assertFileExists($this->appPath.'/resources/js/'.$entry);
        $this->assertFileExists($this->appPath.'/resources/js/pages');
        $this->assertFileExists($this->appPath.'/resources/views/app.blade.php');
        $this->assertFileExists($this->appPath.'/vite.config.js');
        $this->assertFileExists($this->appPath.'/package-lock.json');
        $this->assertSame(['framework' => $framework], json_decode($this->read('saucebase.json'), true));
    }

    /** Laravel's own entry file is no longer the one Vite builds. */
    public function test_it_removes_the_entry_file_laravel_ships(): void
    {
        $this->files->ensureDirectoryExists($this->appPath.'/resources/js');
        $this->files->put($this->appPath.'/resources/js/app.js', "import './bootstrap';\n");

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $this->assertFileDoesNotExist($this->appPath.'/resources/js/app.js');
        $this->assertFileExists($this->appPath.'/resources/js/app.ts');
    }

    /** @return array<string, array{string, string}> */
    public static function frameworks(): array
    {
        return [
            'vue' => ['vue', 'app.ts'],
            'react' => ['react', 'app.tsx'],
        ];
    }

    /** The UI is the application's own now, so nothing may still read it from the package. */
    public function test_the_published_files_point_at_the_application(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        foreach (['tsconfig.json', 'package.json', 'eslint.config.js', 'components.json', 'resources/css/app.css'] as $file) {
            $this->assertStringNotContainsString('saucebase/core/stubs', $this->read($file), $file);
        }

        $this->assertStringContainsString('resources/js', $this->read('tsconfig.json'));
    }

    /**
     * Tailwind scans every file git does not ignore. A new application does not ignore the
     * SSR build, whose source maps hold class names with escaped quotes that are invalid CSS.
     */
    public function test_the_styles_do_not_scan_the_ssr_build(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $this->assertStringContainsString("@source not '../../bootstrap/ssr';", $this->read('resources/css/app.css'));
    }

    /** Core has no sign-in pages, and the dashboard and admin panel redirect guests to one. */
    public function test_it_opens_with_the_saucebase_banner(): void
    {
        putenv('COLUMNS=120');

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])
            ->expectsOutputToContain('Your foundation is ready!')
            ->assertSuccessful();
    }

    public function test_it_keeps_the_banner_out_of_an_unattended_run(): void
    {
        putenv('COLUMNS=120');

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true, '--no-interaction' => true])
            ->doesntExpectOutputToContain('Your foundation is ready!')
            ->assertSuccessful();
    }

    public function test_a_narrow_terminal_gets_the_name_instead_of_wrapped_artwork(): void
    {
        putenv('COLUMNS=60');

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])
            ->doesntExpectOutputToContain('888')
            ->doesntExpectOutputToContain('Your foundation is ready!')
            ->assertSuccessful();
    }

    public function test_it_ignores_the_files_saucebase_generates(): void
    {
        $this->files->put($this->appPath.'/.gitignore', "/vendor\n/public/build\n");

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $ignored = $this->read('.gitignore');

        // Laravel's own lines stay.
        $this->assertStringStartsWith("/vendor\n/public/build\n", $ignored);

        foreach (['/bootstrap/ssr', '/playwright-report', '/test-results', '/public/css/filament', '/public/js/filament', '/public/fonts/filament'] as $path) {
            $this->assertStringContainsString("\n{$path}\n", $ignored);
        }
    }

    public function test_it_does_not_ignore_a_path_twice(): void
    {
        $this->files->put($this->appPath.'/.gitignore', "/vendor\n/bootstrap/ssr\n");

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $this->assertSame(1, substr_count($this->read('.gitignore'), '/bootstrap/ssr'));
    }

    public function test_it_says_to_set_up_boost_for_the_agent(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])
            ->expectsOutputToContain('php artisan boost:install')
            ->assertSuccessful();
    }

    public function test_it_says_to_add_the_auth_module(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])
            ->expectsOutputToContain('composer require saucebase/auth')
            ->assertSuccessful();
    }

    public function test_it_keeps_other_settings_in_saucebase_json(): void
    {
        $this->files->put($this->appPath.'/saucebase.json', '{"ssr": false}');

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        $this->assertSame(['ssr' => false, 'framework' => 'vue'], json_decode($this->read('saucebase.json'), true));
    }

    public function test_it_asks_before_replacing_files(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue'])
            ->expectsConfirmation('Replace these files?', 'no')
            ->expectsOutputToContain('Nothing was installed. Run again with --force to replace these files.')
            ->assertFailed();

        $this->assertSame("<?php\n\n// welcome\n", $this->read('routes/web.php'));
        $this->assertFileDoesNotExist($this->appPath.'/saucebase.json');
    }

    public function test_it_asks_for_the_framework(): void
    {
        $this->artisan('saucebase:install', ['--force' => true])
            ->expectsChoice('Which frontend framework?', 'react', ['vue', 'react'])
            ->assertSuccessful();

        $this->assertFileExists($this->appPath.'/resources/js/app.tsx');
    }

    public function test_it_refuses_an_unknown_framework(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'svelte', '--force' => true])->assertFailed();

        $this->assertFileDoesNotExist($this->appPath.'/saucebase.json');
    }

    public function test_it_refuses_to_install_twice(): void
    {
        $this->files->put($this->appPath.'/saucebase.json', '{"framework": "react"}');

        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertFailed();

        $this->assertFileDoesNotExist($this->appPath.'/resources/js');
    }

    private function read(string $path): string
    {
        return $this->files->get($this->appPath.'/'.$path);
    }
}
