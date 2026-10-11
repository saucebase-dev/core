<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Saucebase\Core\Tests\TestCase;
use Saucebase\Core\TypeScript\TypeScriptConfig;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

/**
 * The frontend's types for what core sends it are generated from the PHP classes, never
 * written by hand. `composer types` rewrites the file; this fails when it is stale.
 */
class GeneratedTypesTest extends TestCase
{
    public function test_the_generated_types_match_the_php_classes(): void
    {
        $committed = dirname(__DIR__, 2).'/resources/js/types/generated.d.ts';
        $directory = sys_get_temp_dir().'/saucebase-types-'.uniqid();

        $this->app->instance(TypeScriptTransformerConfig::class, TypeScriptConfig::make(dirname(__DIR__, 2).'/src', $directory));
        Artisan::call('typescript:transform');

        $generated = file_get_contents($directory.'/generated.d.ts');
        (new Filesystem)->deleteDirectory($directory);

        if (getenv('UPDATE_TYPES')) {
            file_put_contents($committed, $generated);
        }

        $this->assertStringEqualsFile($committed, $generated, 'The generated types are stale. Run `composer types`.');
    }
}
