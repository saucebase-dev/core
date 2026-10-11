<?php

namespace Saucebase\Core\TypeScript;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\Transformed\Untransformable;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;

/**
 * Transforms only enums marked #[TypeScript]; the stock transformer takes every enum.
 */
class AttributedEnumTransformer extends EnumTransformer
{
    public function transform(PhpClassNode $phpClassNode, TransformationContext $context): Transformed|Untransformable
    {
        if ($phpClassNode->getAttributes(TypeScript::class) === []) {
            return Untransformable::create();
        }

        return parent::transform($phpClassNode, $context);
    }
}
