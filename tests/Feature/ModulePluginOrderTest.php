<?php

namespace Saucebase\Core\Tests\Feature;

use Filament\Facades\Filament;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * Module plugins reach the panel in `getNavigationGroupSort()` order, not discovery order.
 *
 * Discovery is alphabetical, so `module-order-fixture` is found first. It declares no
 * sort and must land after `module-test-fixture`, which declares one: the order only
 * comes out right if the sort runs and a missing sort counts as last.
 */
class ModulePluginOrderTest extends TestCase
{
    use InteractsWithFixtureModules;

    public function test_module_plugins_are_registered_in_navigation_group_order(): void
    {
        $moduleIds = array_values(array_intersect(
            array_keys(Filament::getPanel('admin')->getPlugins()),
            ['module-test-fixture', 'module-order-fixture'],
        ));

        $this->assertSame(['module-test-fixture', 'module-order-fixture'], $moduleIds);
    }
}
