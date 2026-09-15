<?php

namespace Saucebase\Core\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * What the application tells search engines: whether it publishes a sitemap and a
 * robots.txt, and which crawl rules the robots.txt carries.
 */
class SeoSettings extends Settings
{
    public bool $sitemap_enabled;

    public bool $robots_enabled;

    /** The crawl rules. The `Sitemap:` line is not stored here; it is appended when served. */
    public string $robots_content;

    public static function group(): string
    {
        return 'seo';
    }
}
