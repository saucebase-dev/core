<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('module-test-fixture.signups', false);
        $this->migrator->add('module-test-fixture.days', 30);
        $this->migrator->add('module-test-fixture.status', 'draft');
        $this->migrator->add('module-test-fixture.secret', 'unchanged');
    }
};
