<?php

namespace Modules\ModuleOrderFixture\Filament;

use Filament\Contracts\Plugin;
use Saucebase\Core\Filament\ModulePlugin;

/** Declares no getNavigationGroupSort(), so it sorts after every module that does. */
class ModuleOrderFixturePlugin implements Plugin
{
    use ModulePlugin;

    public function getId(): string
    {
        return 'module-order-fixture';
    }

    public function getModuleName(): string
    {
        return 'module-order-fixture';
    }
}
