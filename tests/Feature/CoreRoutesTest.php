<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Saucebase\Core\Tests\TestCase;

/**
 * Core registers the routes its own controllers serve, so a plain Laravel app needs
 * nothing in routes/web.php for them.
 */
class CoreRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_registers_its_routes(): void
    {
        $this->assertSame(['web'], $this->middlewareOf('sitemap'));
        $this->assertSame(['web'], $this->middlewareOf('robots'));
        $this->assertSame(['web'], $this->middlewareOf('locale'));
        $this->assertSame(['web', 'auth', 'verified'], $this->middlewareOf('settings'));
        $this->assertSame(['web', 'auth', 'verified'], $this->middlewareOf('home'));
    }

    public function test_the_application_can_replace_a_core_route(): void
    {
        Route::middleware('web')->get('/robots.txt', fn () => 'mine')->name('robots');
        Route::getRoutes()->refreshNameLookups();

        $this->get('/robots.txt')->assertOk()->assertSee('mine');
    }

    /**
     * @return list<string>
     */
    private function middlewareOf(string $name): array
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Core does not register the `{$name}` route.");

        return $route->middleware();
    }
}
