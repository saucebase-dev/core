<?php

namespace Saucebase\Core\Data;

use Saucebase\Core\Enums\ToastPosition;
use Saucebase\Core\Enums\ToastType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The `toast` the server flashes for one request. */
#[TypeScript]
final class ToastData extends Data
{
    public function __construct(
        public string $message,
        public ToastType $type,
        public string|Optional $description = new Optional,
        public ToastActionData|Optional $action = new Optional,
        public int|Optional $duration = new Optional,
        public ToastPosition|Optional $position = new Optional,
    ) {}
}
