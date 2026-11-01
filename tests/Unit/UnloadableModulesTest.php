<?php

namespace Saucebase\Core\Tests\Unit;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\ModuleConfig;
use RuntimeException;
use Saucebase\Core\Tests\TestCase;
use Saucebase\Core\UnloadableModules;

/**
 * A folder in modules/ that Composer doesn't autoload (copied in before `composer require`,
 * left behind by `composer remove`, a psr-4 typo) fails at boot with its name and the fix,
 * not with a route action error, and is never skipped quietly.
 */
class UnloadableModulesTest extends TestCase
{
    public function test_a_module_composer_does_not_autoload_stops_the_boot_with_the_fix(): void
    {
        $modules = new Collection([
            new ModuleConfig('core-like', '/app/modules/core-like', new Collection(['src' => 'Saucebase\\Core\\'])),
            new ModuleConfig('billing', '/app/modules/billing', new Collection(['src' => 'Modules\\Billing\\'])),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('modules/billing');
        $this->expectExceptionMessage('composer require');

        UnloadableModules::refuse($modules);
    }

    public function test_autoloaded_modules_pass(): void
    {
        UnloadableModules::refuse(new Collection([
            new ModuleConfig('core-like', '/app/modules/core-like', new Collection(['src' => 'Saucebase\\Core\\'])),
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_a_module_composer_loads_through_a_classmap_passes(): void
    {
        // e.g. a root `"classmap": ["modules/"]` instead of the module's own psr-4.
        // Composer only lists loaders that have a vendor dir.
        $loader = new ClassLoader(sys_get_temp_dir().'/classmap-vendor');
        $loader->addClassMap(['Modules\\Mapped\\Providers\\MappedServiceProvider' => '/app/modules/mapped/src/Providers/MappedServiceProvider.php']);
        $loader->register();

        try {
            UnloadableModules::refuse(new Collection([
                new ModuleConfig('mapped', '/app/modules/mapped', new Collection(['src' => 'Modules\\Mapped\\'])),
            ]));
            $this->addToAssertionCount(1);
        } finally {
            $loader->unregister();
        }
    }
}
