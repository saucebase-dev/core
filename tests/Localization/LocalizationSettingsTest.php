<?php

namespace Saucebase\Core\Tests\Localization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Saucebase\Core\Localization\LocalizationSettings;
use Saucebase\Core\Tests\Fixtures\User;
use Saucebase\Core\Tests\TestCase;

/**
 * Which languages the application offers, and which one it speaks to whom.
 *
 * The Filament form that toggles them is deliberately untested — its fields are
 * declarations and Filament enforces them. What is ours is here: discovery from the
 * lang directories, and the precedence between a user's stored language, the session
 * and the configured default.
 */
class LocalizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/localization-probe', fn () => Inertia::render('Index'));
    }

    public function test_fresh_install_offers_the_locales_that_ship(): void
    {
        $settings = app(LocalizationSettings::class);

        $this->assertSame(['en', 'pt_BR'], $settings->enabled_locales);
        $this->assertSame('en', $settings->default_locale);
    }

    public function test_available_locales_are_discovered_from_lang_directories(): void
    {
        $available = app(LocalizationSettings::class)->available();

        // Both ship in core; pt_BR also ships inside several modules, which must not
        // produce a duplicate.
        $this->assertSame(['en', 'pt_BR'], array_keys($available));

        // Named from config, so the selector shows the endonym rather than an intl string
        // like "Portuguese (Brazil)".
        $this->assertSame('Português', $available['pt_BR']);
    }

    public function test_disabling_a_locale_removes_it_from_the_shared_prop(): void
    {
        $this->setEnabledLocales(['en'], 'en');

        $this->get('/localization-probe')->assertInertia(
            fn (AssertableInertia $page) => $page->where('locales', ['en' => 'English'])
        );
    }

    public function test_default_locale_applies_when_the_visitor_has_not_chosen(): void
    {
        $this->setEnabledLocales(['en', 'pt_BR'], 'pt_BR');

        $this->get('/localization-probe')->assertOk();

        $this->assertSame('pt_BR', app()->getLocale());
    }

    public function test_a_session_locale_that_is_no_longer_enabled_is_ignored(): void
    {
        $this->setEnabledLocales(['en'], 'en');

        $this->withSession(['locale' => 'pt_BR'])->get('/localization-probe')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_switching_to_an_enabled_locale_is_accepted(): void
    {
        $this->setEnabledLocales(['en', 'pt_BR'], 'en');

        $this->post('/locale/pt_BR')
            ->assertOk()
            ->assertJson(['locale' => 'pt_BR']);

        $this->assertSame('pt_BR', session('locale'));
    }

    public function test_switching_to_a_disabled_locale_is_rejected(): void
    {
        $this->setEnabledLocales(['en'], 'en');

        $this->post('/locale/pt_BR')
            ->assertStatus(400)
            ->assertJson(['error' => 'Invalid locale']);

        $this->assertNull(session('locale'));
    }

    public function test_switching_stores_the_language_on_the_signed_in_user(): void
    {
        $this->setEnabledLocales(['en', 'pt_BR'], 'en');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/locale/pt_BR')->assertOk();

        $this->assertSame('pt_BR', $user->fresh()->locale);
    }

    public function test_a_stored_user_language_survives_a_new_session(): void
    {
        $this->setEnabledLocales(['en', 'pt_BR'], 'en');

        $user = User::factory()->create(['locale' => 'pt_BR']);

        // No session locale at all: a different browser, or a session that has expired.
        $this->actingAs($user)->get('/localization-probe')->assertOk();

        $this->assertSame('pt_BR', app()->getLocale());
    }

    public function test_a_stored_user_language_outranks_the_session(): void
    {
        $this->setEnabledLocales(['en', 'pt_BR'], 'en');

        $user = User::factory()->create(['locale' => 'pt_BR']);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/localization-probe')
            ->assertOk();

        $this->assertSame('pt_BR', app()->getLocale());
    }

    public function test_a_stored_user_language_that_is_no_longer_enabled_falls_back(): void
    {
        $user = User::factory()->create(['locale' => 'pt_BR']);

        $this->setEnabledLocales(['en'], 'en');

        $this->actingAs($user)->get('/localization-probe')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_enabled_never_resolves_to_no_language(): void
    {
        $this->setEnabledLocales([], 'en');

        // An empty setting would otherwise leave the application rendering raw keys.
        $this->assertSame(['en' => 'English'], app(LocalizationSettings::class)->enabled());
    }

    public function test_a_default_locale_that_is_no_longer_enabled_falls_back(): void
    {
        $this->setEnabledLocales(['pt_BR'], 'en');

        $this->get('/localization-probe')->assertOk();

        $this->assertSame('pt_BR', app()->getLocale());
    }

    /**
     * @param  list<string>  $locales
     */
    private function setEnabledLocales(array $locales, string $default): void
    {
        $settings = app(LocalizationSettings::class);
        $settings->enabled_locales = $locales;
        $settings->default_locale = $default;
        $settings->save();

        app()->forgetInstance(LocalizationSettings::class);
    }

    private function actingAsAdmin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::ADMIN);

        $this->actingAs($admin);
    }
}
