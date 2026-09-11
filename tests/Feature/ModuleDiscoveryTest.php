<?php

namespace Saucebase\Core\Tests\Feature;

use InterNACHI\Modular\Support\ModuleRegistry;
use Modules\ModuleTestFixture\Settings\ModuleTestFixtureSection;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * Modules are found on disk, not registered in a list.
 *
 * A module is a directory with a composer.json; nothing enumerates them, so the only
 * way to know the machinery still works is to put one there and look.
 */
class ModuleDiscoveryTest extends TestCase
{
    use InteractsWithFixtureModules;

    public function test_a_module_directory_is_discovered(): void
    {
        $modules = $this->app->make(ModuleRegistry::class)->modules();

        $this->assertCount(1, $modules);
        $this->assertSame('module-test-fixture', $modules->first()->name);
    }

    public function test_a_class_is_traced_back_to_its_module(): void
    {
        $module = $this->app->make(ModuleRegistry::class)
            ->moduleForClass(ModuleTestFixtureSection::class);

        $this->assertNotNull($module, 'ModuleServiceProvider::moduleName() depends on this.');
        $this->assertSame('module-test-fixture', $module->name);
    }

    public function test_module_path_resolves_to_the_module_directory(): void
    {
        $this->assertDirectoryExists(module_path('module-test-fixture'));
        $this->assertFileExists(module_path('module-test-fixture', 'composer.json'));
    }
}
