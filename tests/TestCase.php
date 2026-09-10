<?php

namespace Saucebase\Core\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Orchestra\Testbench\TestCase as Orchestra;
use Saucebase\Core\CoreServiceProvider;
use Saucebase\Core\Http\Controllers\LocalizationController;
use Saucebase\Core\Http\Controllers\SettingsController;
use Saucebase\Core\Http\Middleware\HandleAppearance;
use Saucebase\Core\Http\Middleware\HandleInertiaRequests;
use Saucebase\Core\Http\Middleware\HandleLocalization;
use Saucebase\Core\Tests\Fixtures\Filament\TestPanelProvider;
use Saucebase\Core\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    /**
     * Put core's middleware on the `web` group, as bootstrap/app.php does.
     *
     * Core ships the middleware but does not register it — that stays the
     * application's call — so the test application has to do the same wiring.
     * It happens here rather than in defineEnvironment() because testbench builds
     * the middleware groups after that runs, and would discard the additions.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', HandleAppearance::class);
        $router->pushMiddlewareToGroup('web', HandleLocalization::class);
        $router->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
    }

    /**
     * Every provider a real application would discover, plus core's own.
     *
     * Testbench boots its own skeleton at vendor/orchestra/testbench-core/laravel,
     * which has no vendor/ directory — so Laravel's PackageManifest finds no
     * installed.json and discovers nothing. Naming providers here is the only way
     * they register.
     *
     * They are read out of core's own installed.json rather than hand-listed, for two
     * reasons: a new dependency is registered without anyone remembering this file, and
     * the order matches production exactly, since that is the same file and the same
     * order Laravel reads in a real app.
     *
     * internachi/modular is excluded because CoreServiceProvider registers it itself,
     * after setting `modules_directory` — registering it here would put it first and
     * reproduce the bug that arrangement prevents.
     *
     * TestPanelProvider stands in for the application's AdminPanelProvider, which core
     * does not ship.
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...$this->discoverablePackageProviders(),
            CoreServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    /**
     * @return list<class-string>
     */
    private function discoverablePackageProviders(): array
    {
        $installed = json_decode(
            (string) file_get_contents(__DIR__.'/../vendor/composer/installed.json'),
            true,
        );

        $excluded = ['internachi/modular'];

        return collect($installed['packages'] ?? [])
            ->reject(fn (array $package): bool => in_array($package['name'], $excluded, true))
            ->flatMap(fn (array $package): array => $package['extra']['laravel']['providers'] ?? [])
            ->filter(fn (string $provider): bool => class_exists($provider))
            ->values()
            ->all();
    }

    /**
     * A signed-in user for tests that need one.
     *
     * Core's own code is indifferent to who is signed in; this exists because
     * Filament's panel and the settings routes refuse an anonymous request.
     */
    protected function createUser(): User
    {
        return User::factory()->create();
    }

    protected function defineEnvironment($app): void
    {
        // LocalizationSettings discovers what the application offers by looking for
        // language directories on disk, so the test application needs some. Two, so
        // that "more than one language is enabled" is a state the tests can reach.
        $app->useLangPath(__DIR__.'/Fixtures/lang');

        tap($app->make(Repository::class), function (Repository $config): void {
            $config->set('database.default', 'testing');

            // Any route through the `web` group encrypts cookies, and testbench ships
            // no key.
            $config->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

            // Inertia renders into a root view the application owns; the test
            // application needs its own minimal one.
            $config->set('view.paths', [
                __DIR__.'/Fixtures/views',
                ...$config->get('view.paths', []),
            ]);

            // Filament and the auth middleware resolve the user through the guard, so
            // the fixture has to be the configured provider model.
            $config->set('auth.providers.users.model', User::class);

            // The application publishes this in config/app.php; the localization
            // settings migration seeds the enabled languages from it.
            $config->set('app.available_locales', [
                'en' => 'English',
                'pt_BR' => 'Português',
            ]);
        });
    }

    /**
     * Routes core's code names but does not own.
     *
     * The application registers these in routes/web.php, behind its own middleware —
     * that stays the application's decision (sc-651). Core only needs the names to
     * resolve: `settings` for the modal, `dashboard` because SettingsSection::url()
     * builds its fragment from it, and `index` for the base-route redirect.
     */
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->middleware('web')->group(function (Router $router): void {
            // Inertia responses rather than bare strings: a modal opened on a base
            // route re-renders that route and reads headers off the result.
            $router->get('/', fn () => Inertia::render('Index'))->name('index');
            $router->get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
            $router->get('/settings', SettingsController::class)->name('settings');
            $router->post('/locale/{locale}', LocalizationController::class)->name('locale');
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        // The users table only — the application owns that one, so the test application
        // has to supply it just as a real one does.
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/Database/Migrations');

        // Core's real migrations, not copies of them. Testbench's loadMigrationsFrom()
        // runs a targeted `migrate --path=`, so it does not see the paths
        // CoreServiceProvider registered — naming the directory here is what makes the
        // tests exercise the migrations core actually ships. Second, because
        // add_locale_to_users_table needs the users table above to exist.
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');

        // Core's settings migrations arrive by yet another route: Spatie's provider
        // loads whatever is on settings.migrations_paths, which SettingsServiceProvider
        // has already prepended its own directory to.
    }
}
