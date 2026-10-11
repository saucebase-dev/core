<?php

namespace Saucebase\Core\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ToastType: string
{
    case Default = 'default';
    case Success = 'success';
    case Error = 'error';
    case Info = 'info';
    case Warning = 'warning';
    case Loading = 'loading';
}
