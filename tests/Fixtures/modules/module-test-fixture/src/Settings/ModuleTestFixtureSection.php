<?php

namespace Modules\ModuleTestFixture\Settings;

use Saucebase\Core\Settings\SettingsSection;

class ModuleTestFixtureSection extends SettingsSection
{
    public function slug(): string
    {
        return 'module-test-fixture';
    }

    public function title(): string
    {
        return 'Module Test Fixture';
    }

    public function component(): string
    {
        return 'ModuleTestFixture::SettingsModuleTestFixture';
    }

    public function icon(): ?string
    {
        return 'module-test-fixture';
    }

    public function props(): array
    {
        return [];
    }
}
