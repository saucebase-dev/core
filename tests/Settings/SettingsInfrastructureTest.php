<?php

namespace Saucebase\Core\Tests\Settings;

use Filament\SpatieLaravelSettingsPluginServiceProvider;
use Filament\Support\Enums\Width;
use ReflectionClass;
use Saucebase\Core\Filament\SettingsPage;
use Saucebase\Core\Tests\TestCase;

/**
 * What core provides so that a module can ship a settings page and nothing else.
 *
 * The counterpart assertions — that the application's migrations create the settings
 * table, and that every installed module's page honours the convention — live in the
 * application, because both are facts about a real installation rather than about
 * this package.
 */
class SettingsInfrastructureTest extends TestCase
{
    /**
     * Modules ship settings pages without requiring the plugin themselves, so something
     * beneath them has to carry it. That used to be the application; since the v3 core
     * extraction it is saucebase/core, which owns the SettingsPage base class the pages
     * extend — the dependency now sits with the code that needs it.
     *
     * Presence is asserted, not the constraint: which version satisfies this is
     * `composer.json`'s business, and pinning it here only breaks the test on a bump it
     * has nothing to say about.
     */
    public function test_core_provides_settings_infrastructure_to_modules(): void
    {
        $coreComposer = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertArrayHasKey(
            'filament/spatie-laravel-settings-plugin',
            $coreComposer['require'],
            'The settings plugin must be required by saucebase/core, not by a module.',
        );

        $this->assertTrue(
            class_exists(SpatieLaravelSettingsPluginServiceProvider::class),
            'The settings plugin is declared but not installed.',
        );
    }

    /**
     * The base class exists so the constraint is set once rather than remembered three
     * times. Without a width every settings page fills the viewport, which stretches a
     * single-column form across a wide monitor.
     */
    public function test_the_shared_settings_page_constrains_its_width(): void
    {
        $width = (new ReflectionClass(SettingsPage::class))
            ->getDefaultProperties()['maxContentWidth'] ?? null;

        $this->assertInstanceOf(Width::class, $width);
        $this->assertNotSame(Width::Full, $width);
    }

    public function test_settings_navigation_sort_is_bounded_at_the_integer_limit(): void
    {
        $navigationSort = new ReflectionClass(SettingsPage::class)->getProperty('navigationSort');
        $originalNavigationSort = $navigationSort->getValue();

        try {
            $navigationSort->setValue(null, 1000);
            $this->assertSame(PHP_INT_MAX, SettingsPage::getNavigationSort());

            $navigationSort->setValue(null, 1001);
            $this->assertSame(PHP_INT_MAX, SettingsPage::getNavigationSort());
        } finally {
            $navigationSort->setValue(null, $originalNavigationSort);
        }
    }
}
