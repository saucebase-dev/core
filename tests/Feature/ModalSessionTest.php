<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Saucebase\Core\Tests\TestCase;

/**
 * A modal opened by URL renders its base page through a second request in the
 * same PHP process. That request must share the session the first one started:
 * started again, its save turns the error bag into an array under JSON
 * serialization, and the form's errors vanish.
 */
class ModalSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('session.serialization', 'json');
    }

    public function test_a_form_in_a_modal_keeps_its_errors_with_json_sessions(): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('/form', fn () => Inertia::modal('Form')->baseRoute('index'))->name('form');
            Route::post('/form', fn (Request $request) => $request->validate(['email' => 'required']));
        });

        $this->from('/form')
            ->followingRedirects()
            ->post('/form')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('_inertiaui_modal.component', 'Form')
                ->has('errors.email')
                ->etc());
    }
}
