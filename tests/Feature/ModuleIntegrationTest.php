<?php

namespace Saucebase\Core\Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ServiceProvider;
use Modules\ModuleTestFixture\Filament\ModuleTestFixturePlugin;
use Modules\ModuleTestFixture\Providers\ModuleTestFixtureServiceProvider;
use Saucebase\Breadcrumbs\Breadcrumbs;
use Saucebase\Core\Facades\Navigation;
use Saucebase\Core\Settings\SectionRegistry;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * What a module gets for free by being discovered.
 *
 * Each of these is a separate subsystem reading the same registry, and every one of them
 * fails silently: a module whose settings section is not found, whose navigation file is
 * not loaded or whose Filament plugin is not resolved produces no error, just an
 * application missing a feature.
 */
class ModuleIntegrationTest extends TestCase
{
    use InteractsWithFixtureModules;
    use RefreshDatabase;

    public function test_the_module_provider_registers_its_config_under_the_module_name(): void
    {
        $this->assertSame('Module Test Fixture', config('module-test-fixture.name'));
    }

    public function test_the_module_provider_registers_its_translations_under_the_module_namespace(): void
    {
        $this->assertSame('Module test fixture', __('module-test-fixture::module-test-fixture.title'));
    }

    public function test_a_settings_section_is_discovered_in_the_module(): void
    {
        $section = collect($this->app->make(SectionRegistry::class)->forFrontend())
            ->firstWhere('slug', 'module-test-fixture');

        $this->assertNotNull($section, 'SectionRegistry did not find the module section.');
        $this->assertSame('Module Test Fixture', $section['title']);
        $this->assertSame('ModuleTestFixture::SettingsModuleTestFixture', $section['component']);
        $this->assertSame('module-test-fixture', $section['icon']);
    }

    public function test_the_module_settings_directory_is_registered_with_spatie(): void
    {
        $paths = config('settings.auto_discover_settings');

        $this->assertContains(
            rtrim(str_replace('\\', '/', module_path('module-test-fixture', 'src/Settings')), '/'),
            $paths,
        );
    }

    public function test_module_navigation_is_loaded(): void
    {
        $titles = collect(Navigation::load()->tree())->pluck('title');

        $this->assertContains('Module Test Fixture', $titles);
    }

    /**
     * ModulesPlugin resolves `Modules\<Name>\Filament\<Name>Plugin` by convention, so a
     * renamed class or namespace silently contributes nothing. Asserted through the panel
     * rather than the resolver, because registration is the part that matters.
     */
    public function test_the_module_filament_plugin_is_registered_on_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertInstanceOf(ModuleTestFixturePlugin::class, $panel->getPlugin('module-test-fixture'));
    }

    public function test_module_breadcrumbs_are_registered(): void
    {
        $this->assertTrue(Breadcrumbs::exists('module-test-fixture.index'));
    }

    /**
     * The command resolves paths through module_path(), so it follows the configured
     * modules directory rather than assuming `modules/` under the base path.
     */
    public function test_the_type_generation_command_resolves_a_discovered_module(): void
    {
        $this->artisan('module:generate-types', ['module' => ['module-test-fixture']])
            ->assertSuccessful();

        $this->assertFileExists(module_path('module-test-fixture', 'resources/js/types/generated.d.ts'));
    }

    public function test_the_type_generation_command_reports_an_unknown_module(): void
    {
        $this->artisan('module:generate-types', ['module' => ['nope']])
            ->assertFailed();
    }

    public function test_the_module_public_assets_are_registered_for_publishing(): void
    {
        $paths = ServiceProvider::pathsToPublish(ModuleTestFixtureServiceProvider::class, 'module-assets');

        $this->assertContains(public_path('modules/module-test-fixture'), $paths);
    }

    protected function tearDown(): void
    {
        // The command writes generated types into the fixture module, which is checked in.
        (new Filesystem)->deleteDirectory(module_path('module-test-fixture', 'resources/js'));

        parent::tearDown();
    }
}
