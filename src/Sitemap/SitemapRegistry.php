<?php

namespace Saucebase\Core\Sitemap;

use Closure;
use Spatie\Sitemap\Sitemap;

/**
 * Collects the URLs the application and each module want in `/sitemap.xml`.
 *
 * Contributors register a callback from their service provider's boot(). Callbacks run
 * when the sitemap is requested, not at boot, so a module's queries and route() calls
 * only happen for that one request.
 */
class SitemapRegistry
{
    /** @var list<Closure(Sitemap): mixed> */
    private array $contributors = [];

    /**
     * @param  Closure(Sitemap): mixed  $contributor
     */
    public function add(Closure $contributor): void
    {
        $this->contributors[] = $contributor;
    }

    public function build(): Sitemap
    {
        $sitemap = Sitemap::create();

        foreach ($this->contributors as $contributor) {
            $contributor($sitemap);
        }

        return $sitemap;
    }
}
