<?php

namespace Modules\ModuleTestFixture\Filament;

use Filament\Contracts\Plugin;
use Saucebase\Core\Filament\ModulePlugin;

class ModuleTestFixturePlugin implements Plugin
{
    use ModulePlugin;

    public function getId(): string
    {
        return 'module-test-fixture';
    }

    public function getModuleName(): string
    {
        return 'module-test-fixture';
    }
}
