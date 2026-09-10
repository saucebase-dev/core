<?php

namespace Saucebase\Core\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\QueryBuilder\QueryBuilderServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\SpatieLaravelSettingsPluginServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use Inertia\ServiceProvider;
use InertiaUI\Modal\ModalServiceProvider;
use InterNACHI\Modular\Support\ModularServiceProvider;
use Kirschbaum\PowerJoins\PowerJoinsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Saucebase\Breadcrumbs\BreadcrumbsServiceProvider;
use Saucebase\Core\CoreServiceProvider;
use Saucebase\Core\Frontend\HandleAppearance;
use Saucebase\Core\Inertia\HandleInertiaRequests;
use Saucebase\Core\Localization\HandleLocalization;
use Saucebase\Core\Localization\LocalizationController;
use Saucebase\Core\Settings\SettingsController;
use Saucebase\Core\Tests\Fixtures\Filament\TestPanelProvider;
use Saucebase\Core\Tests\Fixtures\User;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\Navigation\NavigationServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Tighten\Ziggy\ZiggyServiceProvider;

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
     * Providers a real application gets from package discovery.
     *
     * Testbench does not discover the providers of this package's own dependencies,
     * so anything core relies on being registered has to be named here: Spatie's
     * navigation provider binds ActiveUrlChecker to the current request URL,
     * InterNACHI's binds ModuleRegistry, Spatie's settings provider supplies the
     * config SettingsContainer reads at boot, and Spatie's permission provider merges
     * the config PermissionRegistrar reads its model class names from.
     *
     * TestPanelProvider stands in for the application's AdminPanelProvider, which
     * core does not ship.
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            // Filament and its dependencies. A real application gets all of these from
            // package discovery; testbench discovers nothing, and the panel will not
            // resolve without the full set.
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            PowerJoinsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            QueryBuilderServiceProvider::class,
            SpatieLaravelSettingsPluginServiceProvider::class,
            FilamentServiceProvider::class,

            // Core's other runtime dependencies, likewise undiscovered.
            ServiceProvider::class,
            ModalServiceProvider::class,
            BreadcrumbsServiceProvider::class,
            ZiggyServiceProvider::class,

            ModularServiceProvider::class,
            LaravelSettingsServiceProvider::class,
            PermissionServiceProvider::class,
            NavigationServiceProvider::class,
            CoreServiceProvider::class,
            TestPanelProvider::class,
        ];
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
        // Every table the tests need, in one place: users, the permission tables and
        // the settings table. The Spatie packages ship the latter two as `.stub` files
        // for applications to publish rather than as runnable migrations, so the test
        // application carries its own copies — as a real application does.
        //
        // Core's own settings migrations arrive separately: Spatie's provider loads
        // whatever is on settings.migrations_paths, which core's SettingsServiceProvider
        // has already prepended its directory to.
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/Database/Migrations');
    }
}
