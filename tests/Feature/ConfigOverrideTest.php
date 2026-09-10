<?php

namespace Saucebase\Core\Tests\Feature;

use InterNACHI\Modular\Support\ModuleRegistry;
use ReflectionProperty;
use Saucebase\Core\Tests\TestCase;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer;
use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use Spatie\TypeScriptTransformer\Transformers\SpatieEnumTransformer;

/**
 * Pins the config values core applies on behalf of the app.
 *
 * These assertions exist because the failure mode has no symptom at boot: if the
 * override does not land, the app runs happily on the vendor default and the damage
 * shows up somewhere else entirely — a duplicated TypeScript enum, admin pages that
 * refuse to cluster. Asserting the value after a real boot is the only thing that
 * catches a `mergeConfigFrom` regression or a provider-ordering change.
 */
class ConfigOverrideTest extends TestCase
{
    public function test_filament_module_clusters_are_enabled(): void
    {
        $this->assertTrue(config('filament.modules.clusters.enabled'));
        $this->assertFalse(config('filament.modules.clusters.use-top-navigation'));
    }

    public function test_typescript_transformer_collectors_exclude_the_enum_collector(): void
    {
        // EnumCollector plus EnumTransformer emits every enum twice.
        $this->assertNotContains(EnumCollector::class, config('typescript-transformer.collectors'));
    }

    public function test_typescript_transformer_handles_laravel_data_objects(): void
    {
        $transformers = config('typescript-transformer.transformers');

        $this->assertContains(DataTypeScriptTransformer::class, $transformers);
        $this->assertNotContains(SpatieEnumTransformer::class, $transformers);
    }

    public function test_generated_types_land_where_vite_can_see_them(): void
    {
        $this->assertSame(
            base_path('resources/js/types/generated.d.ts'),
            config('typescript-transformer.output_file'),
        );
    }

    public function test_modules_directory_is_set_before_modular_reads_it(): void
    {
        $this->assertSame('modules', config('app-modules.modules_directory'));
    }

    /**
     * The assertion that actually matters.
     *
     * `modules_directory` being right in config proves nothing on its own: modular
     * resolves `ModuleRegistry` during its own `register()` and memoises the path it
     * computes. If core ever loses the race — a dropped `dont-discover`, someone
     * registering `ModularServiceProvider` earlier — the config value stays correct
     * while the registry silently points at `app-modules/` and no module is found.
     *
     * So read the path back off the registry, not the config.
     */
    public function test_module_registry_resolves_against_the_modules_directory(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        $path = (new ReflectionProperty($registry, 'modules_path'))->getValue($registry);

        $this->assertSame(base_path('modules'), $path);
    }

    /**
     * The exporter config has to arrive whole, not as overrides.
     *
     * kkomelin/laravel-translatable-string-exporter only publishes its config; it never
     * calls mergeConfigFrom(). So there is no vendor default to fall back on — any key
     * core fails to supply resolves to null and the exporter misbehaves silently.
     * Asserting a key core has no opinion about is what catches a regression to
     * setting keys piecemeal.
     */
    public function test_string_exporter_config_is_shipped_whole(): void
    {
        $config = config('laravel-translatable-string-exporter');

        // Saucebase's own values.
        $this->assertSame(['app', 'resources', 'modules'], $config['directories']);
        $this->assertContains('*.vue', $config['patterns']);
        $this->assertContains('\$t', $config['functions']);
        $this->assertTrue($config['exclude-translation-keys']);

        // A key core has no opinion on: present only because the file ships entire.
        $this->assertArrayHasKey('sort-keys', $config);
        $this->assertTrue($config['sort-keys']);
    }

    public function test_vendor_defaults_still_merge_for_keys_core_does_not_set(): void
    {
        $this->assertSame('Tests\TestCase', config('app-modules.tests_base'));
    }

    public function test_core_owns_the_saucebase_config(): void
    {
        $this->assertSame(
            ['Basic Recipe' => 'stubs/saucebase/recipes/basic'],
            config('saucebase.template'),
        );
        $this->assertSame(['module.json'], config('saucebase.ignore_files'));
    }
}
