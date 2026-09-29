<?php

namespace Modules\ModuleTestFixture\Data;

use Modules\ModuleTestFixture\Enums\FixtureStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FixtureData extends Data
{
    public function __construct(
        public string $title,
        public ?int $count,
        public FixtureStatus $status,
    ) {}
}
