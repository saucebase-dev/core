<?php

namespace Saucebase\Core\TypeScript;

use Spatie\LaravelTypeScriptTransformer\LaravelData\Transformers\DataClassTransformer;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;

/**
 * Transforms only laravel-data classes marked #[TypeScript]; the stock transformer takes every one.
 */
class AttributedDataTransformer extends DataClassTransformer
{
    protected function shouldTransform(PhpClassNode $phpClassNode): bool
    {
        return parent::shouldTransform($phpClassNode)
            && $phpClassNode->getAttributes(TypeScript::class) !== [];
    }
}
