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

        // Brand assets hold uploads only. Null means "use the app's fallback", which
        // lives with the app's views, not here.
        foreach (['site_logo_on_light', 'site_logo_on_dark', 'site_icon_on_light', 'site_icon_on_dark'] as $asset) {
            if (! $this->migrator->exists("general.{$asset}")) {
                $this->migrator->add("general.{$asset}", null);
            }
        }
    }
};
