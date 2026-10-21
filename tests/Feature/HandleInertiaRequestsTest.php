<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Saucebase\Breadcrumbs\Breadcrumbs;
use Saucebase\Breadcrumbs\Generator;
use Saucebase\Core\Http\Middleware\HandleInertiaRequests;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * Props shared with every Inertia page. Asserted by value: the frontend reads them
 * on every request, so a dropped or renamed key breaks pages silently.
 */
class HandleInertiaRequestsTest extends TestCase
{
    use InteractsWithFixtureModules;
    use RefreshDatabase;

    public function test_the_current_locale_is_shared(): void
    {
        app()->setLocale('pt_BR');

        $this->assertSame('pt_BR', $this->share()['locale']);
    }

    public function test_enabled_modules_are_shared_keyed_by_name(): void
    {
        $modules = ($this->share()['modules'])();

        $this->assertSame('module-test-fixture', $modules['module-test-fixture']);
    }

    public function test_ziggy_routes_and_the_current_location_are_shared(): void
    {
        $ziggy = ($this->share('/some/page')['ziggy'])();

        $this->assertSame('http://localhost/some/page', $ziggy['location']);
        $this->assertArrayHasKey('routes', $ziggy);
    }

    public function test_breadcrumbs_come_from_the_route_definition_with_a_translation_prefix(): void
    {
        $route = Route::get('/fixture', fn () => 'ok')->name('module-test-fixture.index');

        $breadcrumbs = ($this->share('/fixture', $route)['breadcrumbs'])();

        $this->assertSame('breadcrumbs.module-test-fixture.index', $breadcrumbs[0]['title']);
        $this->assertSame(route('dashboard'), $breadcrumbs[0]['url']);
        $this->assertSame([], $breadcrumbs[0]['attributes']);
    }

    public function test_breadcrumb_attributes_are_passed_to_the_frontend(): void
    {
        Breadcrumbs::for('with-data', fn (Generator $trail) => $trail->push('with-data', '/with-data', ['data' => ['icon' => 'home']]));
        $route = Route::get('/with-data', fn () => 'ok')->name('with-data');

        $breadcrumbs = ($this->share('/with-data', $route)['breadcrumbs'])();

        $this->assertSame(['icon' => 'home'], $breadcrumbs[0]['attributes']);
    }

    public function test_breadcrumbs_fall_back_to_navigation_when_the_route_has_none(): void
    {
        Exceptions::fake();
        $route = Route::get('/plain', fn () => 'ok')->name('plain');

        $this->assertSame([], ($this->share('/plain', $route)['breadcrumbs'])());
        Exceptions::assertNothingReported();
    }

    public function test_breadcrumbs_fall_back_to_navigation_outside_a_route(): void
    {
        $this->assertSame([], ($this->share()['breadcrumbs'])());
    }

    private function share(string $uri = '/', ?\Illuminate\Routing\Route $route = null): array
    {
        $request = Request::create($uri);
        $request->setLaravelSession($this->app['session.store']);

        if ($route) {
            $request->setRouteResolver(fn () => $route->bind($request));
        }

        $this->app->instance('request', $request);

        return app(HandleInertiaRequests::class)->share($request);
    }
}
