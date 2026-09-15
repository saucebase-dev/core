<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seo.sitemap_enabled', true);
        $this->migrator->add('seo.robots_enabled', true);
        $this->migrator->add('seo.robots_content', "User-agent: *\nDisallow: /admin");
    }
};
