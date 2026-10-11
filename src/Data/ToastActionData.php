<?php

namespace Saucebase\Core\Data;

use Saucebase\Core\Enums\ToastActionType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class ToastActionData extends Data
{
    public function __construct(
        public string $label,
        public ToastActionType|Optional $type = new Optional,
        public string|Optional $onClick = new Optional,
    ) {}
}
