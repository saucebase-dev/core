<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;
use Saucebase\Core\Settings\GeneralSettings;
use Saucebase\Core\Tests\TestCase;

/**
 * The application's identity: what it calls itself, and the marks it wears.
 *
 * The Filament form that edits these is deliberately untested. Its fields are
 * declarations — `->required()`, `->image()`, `->maxSize(1024)` — and the engine that
 * enforces them is Filament's, tested by Filament. What is ours is below: the
 * migration's defaults, how a stored path becomes a URL, and what reaches the
 * front-end.
 */
class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/general-settings-probe', fn () => Inertia::render('Index'));
    }

    public function test_fresh_install_has_general_settings_defaults(): void
    {
        $settings = app(GeneralSettings::class);

        // The migration seeds the name from the application's own, rather than a
        // literal, so a fresh install introduces itself correctly without being told.
        $this->assertSame(config('app.name'), $settings->site_name);
        $this->assertNull($settings->site_tagline);
        $this->assertNull($settings->site_description);

        // No brand images, so the logo and the favicon both stay the ones that ship.
        $this->assertNull($settings->site_icon);
        $this->assertNull($settings->site_logo);
        $this->assertFalse($settings->prefer_logo);
    }

    public function test_general_settings_are_shared_with_inertia(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('site-branding/icon.png', 'icon');

        $settings = app(GeneralSettings::class);
        $settings->site_name = 'Acme Platform';
        $settings->site_tagline = 'The modular SaaS starter kit';
        $settings->site_description = 'The Acme customer platform.';
        $settings->site_icon = 'site-branding/icon.png';
        $settings->site_logo = 'https://cdn.example.com/logo.svg';
        $settings->save();

        $this->get('/general-settings-probe')
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('settings.general.site_name', 'Acme Platform')
                ->where('settings.general.site_tagline', 'The modular SaaS starter kit')
                ->where('settings.general.site_description', 'The Acme customer platform.')
                ->where('settings.general.site_icon', Storage::disk('public')->url('site-branding/icon.png'))
                ->where('settings.general.site_logo', 'https://cdn.example.com/logo.svg'));
    }

    /**
     * An uploaded file is a path on the public disk; anything else is already a URL.
     *
     * Passing a root-relative path through Storage::url() would prefix it a second
     * time — /storage/storage/... — which is why publicFileUrl() checks the shape
     * before resolving.
     */
    public function test_root_relative_branding_urls_are_not_resolved_as_storage_paths(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->site_icon = '/storage/tenant-logos/icon.png';
        $settings->site_logo = '/storage/tenant-logos/logo.png';

        $this->assertSame('/storage/tenant-logos/icon.png', $settings->siteIconUrl());
        $this->assertSame('/storage/tenant-logos/logo.png', $settings->siteLogoUrl());
    }
}
