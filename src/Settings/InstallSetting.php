<?php

namespace Saucebase\Core\Settings;

use Attribute;

/**
 * Marks a settings property as one an install may set: from a template's
 * `extra.saucebase.configure`, a `--set=module.property=value` flag, or a
 * prompt. The property's type picks the prompt: bool a confirm, int and string a text
 * field, a backed enum a select. Unmarked properties are never set at install.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class InstallSetting
{
    /**
     * @param  string  $label  The question, a translation key.
     * @param  list<string>  $rules  Validation rules, run on every value: prompt, flag and template.
     */
    public function __construct(
        public readonly string $label,
        public readonly array $rules = [],
        public readonly ?string $hint = null,
    ) {}
}
