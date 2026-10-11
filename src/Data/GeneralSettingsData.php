<?php

namespace Saucebase\Core\Data;

use Saucebase\Core\Settings\GeneralSettings;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Who the application says it is, as every page receives it.
 *
 * The four brand assets are resolved URLs rather than stored paths, so the frontend
 * never has to know where an upload lives. Null means nobody uploaded one, and the
 * frontend renders the application's own artwork in its place. The suffix names the
 * background the asset sits on, not the colour of its ink: `on_dark` is the
 * light-coloured artwork.
 */
#[TypeScript]
final class GeneralSettingsData extends Data
{
    public function __construct(
        public string $site_name,
        public ?string $site_tagline,
        public ?string $site_description,
        public ?string $site_logo_on_light,
        public ?string $site_logo_on_dark,
        public ?string $site_icon_on_light,
        public ?string $site_icon_on_dark,
    ) {}

    public static function fromSettings(GeneralSettings $settings): self
    {
        return new self(
            site_name: $settings->site_name,
            site_tagline: $settings->site_tagline,
            site_description: $settings->site_description,
            site_logo_on_light: $settings->logoOnLightUrl(),
            site_logo_on_dark: $settings->logoOnDarkUrl(),
            site_icon_on_light: $settings->iconOnLightUrl(),
            site_icon_on_dark: $settings->iconOnDarkUrl(),
        );
    }
}
