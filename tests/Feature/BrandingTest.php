<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Saucebase\Core\Settings\GeneralSettings;
use Saucebase\Core\Tests\TestCase;

/**
 * The brand is four assets that are always set.
 *
 * Consumers render them without checking for null. If a default ever resolved to null
 * nothing would throw — the application would serve broken images everywhere — so the
 * guarantee is asserted here rather than left to the type declarations.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_brand_asset_has_a_shipped_default(): void
    {
        $brand = $this->app->make(GeneralSettings::class);

        $this->assertSame('/images/logo-on-light.svg', $brand->site_logo_on_light);
        $this->assertSame('/images/logo-on-dark.svg', $brand->site_logo_on_dark);
        $this->assertSame('/images/icon-on-light.svg', $brand->site_icon_on_light);
        $this->assertSame('/images/icon-on-dark.svg', $brand->site_icon_on_dark);
    }

    public function test_shipped_defaults_are_served_as_document_root_paths(): void
    {
        $brand = $this->app->make(GeneralSettings::class);

        // A shipped asset is already a path under the document root and must be left
        // alone; only an uploaded key gets resolved through the public disk.
        $this->assertSame('/images/icon-on-light.svg', $brand->iconOnLightUrl());
        $this->assertSame('/images/logo-on-dark.svg', $brand->logoOnDarkUrl());
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

    public function test_the_shipped_artwork_exists_on_disk(): void
    {
        foreach (['logo-on-light', 'logo-on-dark', 'icon-on-light', 'icon-on-dark'] as $asset) {
            $this->assertFileExists(
                dirname(__DIR__, 2)."/public/images/{$asset}.svg",
                "Core defaults to /images/{$asset}.svg but does not ship it.",
            );
        }
    }
}
