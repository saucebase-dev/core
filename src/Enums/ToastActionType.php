<?php

namespace Saucebase\Core\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** What a toast's button does with its `onClick` value. */
#[TypeScript]
enum ToastActionType: string
{
    case Route = 'route';
    case Url = 'url';
    case ExternalUrl = 'external_url';
    case Function = 'function';
}
