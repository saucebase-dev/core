<?php

namespace Modules\ModuleTestFixture\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum FixtureStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
