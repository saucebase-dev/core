<?php

namespace Saucebase\Core\Settings;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\LaravelSettings\Settings;

/**
 * Who the application says it is.
 *
 * Every brand-rendering surface resolves this — the logo, the sidebar, the page title,
 * the favicon — so anything that decorates this object rebrands all of them at once.
 *
 * The four brand assets are always set. They default to the files core ships and
 * publishes, and an upload replaces the path rather than filling an empty one. That is
 * deliberate: with no null state, no consumer needs a "nothing configured" branch, which
 * is what lets the logo component be an `<img>` and nothing more.
 */
class GeneralSettings extends Settings
{
    /** What the application calls itself, suffixed onto every page title. */
    public string $site_name;

    /** A short line under the name, on the pages that show one. */
    public ?string $site_tagline;

    /** The longer blurb, used as the meta description. */
    public ?string $site_description;

    /**
     * The wide lockup, for slots with room for one. It carries the application's name
     * itself, so nothing draws the name beside it.
     *
     * The suffix names the background the asset sits on, not the colour of its ink:
     * `on_dark` is the light-coloured artwork.
     */
    public string $site_logo_on_light;

    public string $site_logo_on_dark;

    /** The square mark, for where only a mark fits — a collapsed sidebar, a tab icon. */
    public string $site_icon_on_light;

    public string $site_icon_on_dark;

    public function logoOnLightUrl(): string
    {
        return $this->publicFileUrl($this->site_logo_on_light);
    }

    public function logoOnDarkUrl(): string
    {
        return $this->publicFileUrl($this->site_logo_on_dark);
    }

    public function iconOnLightUrl(): string
    {
        return $this->publicFileUrl($this->site_icon_on_light);
    }

    public function iconOnDarkUrl(): string
    {
        return $this->publicFileUrl($this->site_icon_on_dark);
    }

    /**
     * The blurb for `<meta name="description">`, or null to omit the tag.
     *
     * Falls back to the tagline, then to nothing: an empty description element fails the
     * same search-engine audit as a missing one, so the caller has to be able to skip it.
     */
    public function metaDescription(): ?string
    {
        return Str::squish((string) ($this->site_description ?: $this->site_tagline)) ?: null;
    }

    public static function group(): string
    {
        return 'general';
    }

    /**
     * A shipped asset is a path under the document root; an uploaded one is a key on the
     * public disk. Telling them apart by shape means an upload and a default can sit in
     * the same field.
     */
    private function publicFileUrl(string $path): string
    {
        return Str::startsWith($path, '/') || Str::isUrl($path)
            ? $path
            : Storage::disk('public')->url($path);
    }
}
