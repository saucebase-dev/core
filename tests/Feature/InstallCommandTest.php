<?php

namespace Saucebase\Core\Tests\Feature;

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

    public function test_the_published_example_test_migrates_the_database(): void
    {
        $this->artisan('saucebase:install', ['framework' => 'vue', '--force' => true])->assertSuccessful();

        // Laravel's own example test requests the home page, which now reads the settings table.
        $this->assertStringContainsString('use RefreshDatabase;', $this->read('tests/Feature/ExampleTest.php'));
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
        ], $composer['scripts']['post-autoload-dump']);
        $this->assertSame('acme/app', $composer['name']);
        $this->assertStringContainsString('"dont-discover": []', $this->read('composer.json'));
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

    /** Core has no sign-in pages, and the dashboard and admin panel redirect guests to one. */
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
