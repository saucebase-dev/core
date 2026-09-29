<?php

namespace Saucebase\Core\TypeScript;

use Carbon\CarbonInterface;
use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelTypeScriptTransformer\Transformers\LaravelAttributedClassTransformer;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptString;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

/**
 * Builds the typescript-transformer config for one source tree.
 *
 * Only classes marked #[TypeScript] are emitted, and Laravel's paginator
 * types are left out, so each generated.d.ts holds just its own module's types.
 */
class TypeScriptConfig
{
    public static function make(string $sourcePath, string $outputDirectory): TypeScriptTransformerConfig
    {
        (new Filesystem)->ensureDirectoryExists($outputDirectory);

        return (new TypeScriptTransformerConfigFactory)
            ->transformer(
                AttributedDataTransformer::class,
                AttributedEnumTransformer::class,
                LaravelAttributedClassTransformer::class,
            )
            ->replaceType(CarbonInterface::class, new TypeScriptString)
            ->transformDirectories($sourcePath)
            ->outputDirectory($outputDirectory)
            ->writer(new GlobalNamespaceWriter('generated.d.ts'))
            ->withoutManifest()
            ->get();
    }
}
