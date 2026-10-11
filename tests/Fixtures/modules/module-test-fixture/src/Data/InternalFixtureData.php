<?php

namespace Modules\ModuleTestFixture\Data;

use Spatie\LaravelData\Data;

class InternalFixtureData extends Data
{
    public function __construct(
        public string $secret,
    ) {}
}
