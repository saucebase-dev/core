<?php

namespace Saucebase\Core\Tests\Feature;

use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * A module registers before core when its package name sorts first.
 *
 * Laravel registers discovered packages alphabetically, so `saucebase/auth` reaches
 * its provider's register() before `saucebase/core` has bound the module registry.
 */
class ModuleBootOrderTest extends TestCase
{
    use InteractsWithFixtureModules;

    protected function getPackageProviders($app): array
    {
        return [...$this->fixtureModuleProviders(), ...parent::getPackageProviders($app)];
    }

    public function test_a_module_registered_before_core_still_merges_its_config(): void
    {
        $this->assertSame('Module Test Fixture', config('module-test-fixture.name'));
    }
}
