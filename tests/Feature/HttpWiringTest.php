<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Saucebase\Core\Facades\Home;
use Saucebase\Core\Http\Middleware\HandleAppearance;
use Saucebase\Core\Http\Middleware\HandleInertiaRequests;
use Saucebase\Core\Http\Middleware\HandleLocalization;
use Saucebase\Core\Tests\Fixtures\User;
use Saucebase\Core\Tests\TestCase;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

/**
 * Core wires itself into a plain Laravel application: nothing in bootstrap/app.php.
 */
class HttpWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_middleware_runs_on_every_web_request_in_order(): void
    {
        $web = $this->app->make(Kernel::class)->getMiddlewareGroups()['web'];

        $this->assertSame(
            [HandleAppearance::class, HandleLocalization::class, HandleInertiaRequests::class],
            array_values(array_intersect($web, [HandleAppearance::class, HandleLocalization::class, HandleInertiaRequests::class])),
        );
    }

    public function test_the_appearance_cookie_is_readable_by_the_server(): void
    {
        $this->assertTrue($this->app->make(EncryptCookies::class)->isDisabled('appearance'));
    }

    public function test_modules_can_guard_routes_by_permission(): void
    {
        $aliases = $this->app->make('router')->getMiddleware();

        $this->assertSame(RoleMiddleware::class, $aliases['role']);
        $this->assertSame(PermissionMiddleware::class, $aliases['permission']);
        $this->assertSame(RoleOrPermissionMiddleware::class, $aliases['role_or_permission']);
    }

    public function test_a_signed_in_user_on_a_guest_page_is_sent_home(): void
    {
        Home::using(fn (): string => url('/chat'));
        Route::middleware(['web', 'guest'])->get('/guest-only', fn () => 'guest');

        $this->actingAs(User::factory()->create())
            ->get('/guest-only')
            ->assertRedirect(url('/chat'));
    }
}
