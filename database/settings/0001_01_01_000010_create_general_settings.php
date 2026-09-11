<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('general.site_name')) {
            $this->migrator->add('general.site_name', config('app.name', 'Saucebase'));
        }

        if (! $this->migrator->exists('general.site_tagline')) {
            $this->migrator->add('general.site_tagline', null);
        }

        if (! $this->migrator->exists('general.site_description')) {
            $this->migrator->add('general.site_description', null);
        }

        // Brand assets are never null: they point at the files core ships and publishes,
        // and an upload replaces the path. Keeping a real value here is what lets every
        // consumer drop its "nothing configured" branch.
        //
        // The suffix names the background the asset sits on, not the colour of its ink —
        // `logo-on-dark` is the light-coloured one.
        foreach ([
            'general.site_logo_on_light' => '/images/logo-on-light.svg',
            'general.site_logo_on_dark' => '/images/logo-on-dark.svg',
            'general.site_icon_on_light' => '/images/icon-on-light.svg',
            'general.site_icon_on_dark' => '/images/icon-on-dark.svg',
        ] as $property => $default) {
            if (! $this->migrator->exists($property)) {
                $this->migrator->add($property, $default);
            }
        }
    }
};
