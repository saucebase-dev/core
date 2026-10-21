<?php

namespace Saucebase\Core\Tests\Unit\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Saucebase\Core\Filament\ModulePlugin;
use Saucebase\Core\Tests\TestCase;

/**
 * A module plugin points Filament discovery at the module's own folders and namespace.
 * A wrong path or namespace registers nothing and fails silently, so both are asserted.
 */
class ModulePluginTest extends TestCase
{
    public function test_pages_resources_and_widgets_are_discovered_in_the_module(): void
    {
        $panel = $this->registerPlugin();
        $src = module_path('Shop', 'src').DIRECTORY_SEPARATOR.'Filament'.DIRECTORY_SEPARATOR;

        $this->assertSame([$src.'Pages'], $panel->getPageDirectories());
        $this->assertSame(['Modules\\Shop\\Filament\\Pages'], $panel->getPageNamespaces());
        $this->assertSame([$src.'Resources'], $panel->getResourceDirectories());
        $this->assertSame(['Modules\\Shop\\Filament\\Resources'], $panel->getResourceNamespaces());
        $this->assertSame([$src.'Widgets'], $panel->getWidgetDirectories());
        $this->assertSame(['Modules\\Shop\\Filament\\Widgets'], $panel->getWidgetNamespaces());
    }

    public function test_clusters_are_not_discovered_when_the_setting_is_missing(): void
    {
        config(['filament.modules.clusters' => []]);

        $this->assertSame([], $this->registerPlugin()->getClusterDirectories());
    }

    public function test_clusters_are_discovered_when_enabled(): void
    {
        config(['filament.modules.clusters.enabled' => true]);

        $panel = $this->registerPlugin();

        $this->assertSame(
            [module_path('Shop', 'src').DIRECTORY_SEPARATOR.'Filament'.DIRECTORY_SEPARATOR.'Clusters'],
            $panel->getClusterDirectories(),
        );
        $this->assertSame(['Modules\\Shop\\Filament\\Clusters'], $panel->getClusterNamespaces());
    }

    public function test_the_after_register_hook_runs(): void
    {
        $plugin = $this->plugin();
        $panel = Panel::make()->id('test');

        $plugin->register($panel);

        $this->assertSame($panel, $plugin->registeredOn);
    }

    private function registerPlugin(): Panel
    {
        $panel = Panel::make()->id('test');
        $this->plugin()->register($panel);

        return $panel;
    }

    private function plugin(): Plugin
    {
        return new class implements Plugin
        {
            use ModulePlugin;

            public ?Panel $registeredOn = null;

            public function getId(): string
            {
                return 'shop';
            }

            public function getModuleName(): string
            {
                return 'Shop';
            }

            public function boot(Panel $panel): void
            {
                // Required by the Plugin contract; not under test.
            }

            public function afterRegister(Panel $panel)
            {
                $this->registeredOn = $panel;
            }
        };
    }
}
