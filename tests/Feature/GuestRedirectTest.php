<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Saucebase\Core\Tests\TestCase;

class GuestRedirectTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        $router->middleware(['web', 'auth'])->get('/members', fn () => 'members');
    }

    /** Core has no sign-in page of its own, so without one the page does not exist for a guest. */
    public function test_a_guest_gets_a_404_when_the_application_has_no_login_route(): void
    {
        $this->get('/members')->assertNotFound();
    }

    public function test_a_guest_is_sent_to_the_login_route_when_there_is_one(): void
    {
        Route::get('/sign-in', fn () => 'sign in')->name('login');
        Route::getRoutes()->refreshNameLookups();

        $this->get('/members')->assertRedirect('/sign-in');
    }

    public function test_any_other_missing_route_is_still_an_error(): void
    {
        Route::middleware('web')->get('/broken', fn () => route('nowhere'));

        $this->get('/broken')->assertServerError();
    }
}
