<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Saucebase\Core\Settings\GeneralSettings;
use Saucebase\Core\Tests\TestCase;

/**
 * The brand is four optional assets.
 *
 * A setting holds what the owner uploaded, or null. Which artwork stands in for a
 * missing one is the application's call, made where it renders — core never stores a
 * path into the app's public directory, so renaming the app's default files cannot
 * leave the database pointing at nothing.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_assets_are_null_until_uploaded(): void
    {
        $brand = $this->app->make(GeneralSettings::class);

        $this->assertNull($brand->site_logo_on_light);
        $this->assertNull($brand->site_logo_on_dark);
        $this->assertNull($brand->site_icon_on_light);
        $this->assertNull($brand->site_icon_on_dark);
    }

    public function test_urls_are_null_when_nothing_is_uploaded(): void
    {
        $brand = $this->app->make(GeneralSettings::class);

        $this->assertNull($brand->logoOnLightUrl());
        $this->assertNull($brand->logoOnDarkUrl());
        $this->assertNull($brand->iconOnLightUrl());
        $this->assertNull($brand->iconOnDarkUrl());
    }

    public function test_an_uploaded_asset_resolves_through_the_public_disk(): void
    {
        $brand = $this->app->make(GeneralSettings::class);
        $brand->site_logo_on_light = 'site-branding/acme.svg';

        // Resolved through the disk rather than returned verbatim — that is the
        // difference between an uploaded key and a shipped path.
        $this->assertSame(
            Storage::disk('public')->url('site-branding/acme.svg'),
            $brand->logoOnLightUrl(),
        );
    }

    public function test_the_publishable_artwork_exists_on_disk(): void
    {
        foreach (['logo-on-light', 'logo-on-dark', 'icon-on-light', 'icon-on-dark'] as $asset) {
            $this->assertFileExists(
                dirname(__DIR__, 2)."/public/images/{$asset}.svg",
                "Core publishes /images/{$asset}.svg for the app's fallback but does not ship it.",
            );
        }
    }

    public function test_meta_description_prefers_the_description_then_the_tagline(): void
    {
        $settings = app(GeneralSettings::class);

        $settings->site_tagline = 'A tagline';
        $settings->site_description = 'A description';
        $this->assertSame('A description', $settings->metaDescription());

        $settings->site_description = '';
        $this->assertSame('A tagline', $settings->metaDescription());
    }

    public function test_meta_description_is_null_when_nothing_is_set(): void
    {
        $settings = app(GeneralSettings::class);

        $settings->site_tagline = null;
        $settings->site_description = '   ';

        $this->assertNull($settings->metaDescription(), 'An empty description tag fails the same audit as a missing one.');
    }
}
