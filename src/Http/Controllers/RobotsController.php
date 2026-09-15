<?php

namespace Saucebase\Core\Http\Controllers;

use Illuminate\Http\Response;
use Saucebase\Core\Settings\SeoSettings;

class RobotsController
{
    /**
     * The `Sitemap:` line has to be an absolute URL, which a static public/robots.txt
     * cannot know, so it is added here from the current host.
     */
    public function __invoke(SeoSettings $settings): Response
    {
        abort_unless($settings->robots_enabled, 404);

        $content = trim($settings->robots_content);

        if ($settings->sitemap_enabled && app('router')->has('sitemap')) {
            $content .= "\n\nSitemap: ".route('sitemap');
        }

        return response($content."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
