<?php

namespace Saucebase\Core\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Rich-editor HTML that a page renders as markup (`v-html`, `dangerouslySetInnerHTML`).
 *
 * Cleaned on every read with Filament's sanitizer (`Str::sanitizeHtml()`), so scripts and
 * event handlers never reach a visitor whoever wrote the value: the admin, an import, a
 * provider. Stored as written, so tightening the sanitizer later loses nothing.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class SanitizedHtml implements CastsAttributes
{
    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Str::sanitizeHtml((string) $value);
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
