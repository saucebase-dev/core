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
 * The four brand assets hold what the owner uploaded, or null. Which artwork stands in
 * for a missing one is decided where it renders — the app's logo component and root
 * view — so core never stores a path into the app's public directory.
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
    public ?string $site_logo_on_light;

    public ?string $site_logo_on_dark;

    /** The square mark, for where only a mark fits — a collapsed sidebar, a tab icon. */
    public ?string $site_icon_on_light;

    public ?string $site_icon_on_dark;

    public function logoOnLightUrl(): ?string
    {
        return $this->publicFileUrl($this->site_logo_on_light);
    }

    public function logoOnDarkUrl(): ?string
    {
        return $this->publicFileUrl($this->site_logo_on_dark);
    }

    public function iconOnLightUrl(): ?string
    {
        return $this->publicFileUrl($this->site_icon_on_light);
    }

    public function iconOnDarkUrl(): ?string
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
     * An uploaded file is a key on the public disk; a root-relative path or absolute URL
     * (set by code, such as a tenant override) is already servable and passes through.
     */
    private function publicFileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, '/') || Str::isUrl($path)
            ? $path
            : Storage::disk('public')->url($path);
    }
}
