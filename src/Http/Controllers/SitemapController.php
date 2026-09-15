<?php

namespace Saucebase\Core\Http\Controllers;

use Saucebase\Core\Settings\SeoSettings;
use Saucebase\Core\Sitemap\SitemapRegistry;
use Spatie\Sitemap\Sitemap;

class SitemapController
{
    // ponytail: built per request; cache the rendered XML if contributors get slow.
    public function __invoke(SitemapRegistry $registry, SeoSettings $settings): Sitemap
    {
        abort_unless($settings->sitemap_enabled, 404);

        return $registry->build();
    }
}
