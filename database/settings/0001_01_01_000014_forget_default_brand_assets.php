<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Brand assets used to default to the app's shipped artwork paths. They now hold
 * uploads only, so an install still carrying one of those paths gets null back and
 * falls through to the app's own fallback. Uploads are left alone.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach ([
            'site_logo_on_light' => '/images/logo-on-light.svg',
            'site_logo_on_dark' => '/images/logo-on-dark.svg',
            'site_icon_on_light' => '/images/icon-on-light.svg',
            'site_icon_on_dark' => '/images/icon-on-dark.svg',
        ] as $asset => $shippedPath) {
            $property = "general.{$asset}";

            if ($this->migrator->exists($property)) {
                $this->migrator->update($property, fn (?string $value) => $value === $shippedPath ? null : $value);
            }
        }
    }
};
