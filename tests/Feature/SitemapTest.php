<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Saucebase\Core\Http\Controllers\RobotsController;
use Saucebase\Core\Http\Controllers\SitemapController;
use Saucebase\Core\Settings\SeoSettings;
use Saucebase\Core\Sitemap\SitemapRegistry;
use Saucebase\Core\Tests\TestCase;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
        Route::get('/robots.txt', RobotsController::class);
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_sitemap_lists_urls_from_every_contributor(): void
    {
        $registry = $this->app->make(SitemapRegistry::class);
        $registry->add(fn (Sitemap $sitemap) => $sitemap->add(Url::create('https://example.test/')));
        $registry->add(fn (Sitemap $sitemap) => $sitemap->add(Url::create('https://example.test/blog')));

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>https://example.test/</loc>', false)
            ->assertSee('<loc>https://example.test/blog</loc>', false);
    }

    public function test_disabled_sitemap_is_not_found(): void
    {
        $this->updateSettings(sitemap_enabled: false);

        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_robots_serves_the_saved_rules_and_the_absolute_sitemap_url(): void
    {
        $this->updateSettings(robots_content: "User-agent: *\nDisallow: /private");

        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame(
            "User-agent: *\nDisallow: /private\n\nSitemap: ".url('/sitemap.xml')."\n",
            $response->getContent(),
        );
    }

    public function test_robots_omits_the_sitemap_line_when_the_sitemap_is_disabled(): void
    {
        $this->updateSettings(sitemap_enabled: false);

        $this->get('/robots.txt')->assertOk()->assertDontSee('Sitemap:');
    }

    public function test_disabled_robots_is_not_found(): void
    {
        $this->updateSettings(robots_enabled: false);

        $this->get('/robots.txt')->assertNotFound();
    }

    private function updateSettings(?bool $sitemap_enabled = null, ?bool $robots_enabled = null, ?string $robots_content = null): void
    {
        $settings = app(SeoSettings::class);
        $settings->sitemap_enabled = $sitemap_enabled ?? $settings->sitemap_enabled;
        $settings->robots_enabled = $robots_enabled ?? $settings->robots_enabled;
        $settings->robots_content = $robots_content ?? $settings->robots_content;
        $settings->save();
    }
}
