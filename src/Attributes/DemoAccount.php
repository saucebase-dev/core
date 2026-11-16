<?php

namespace Saucebase\Core\Attributes;

use Attribute;

/**
 * An account a state seeder creates, declared on the seeder: `modules:demo-accounts`
 * lists them, and a featured one is named where people start (the installer's summary,
 * the end of `modules:seed`). Feature the one or two to sign in with first.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DemoAccount
{
    public function __construct(
        public readonly string $email,
        public readonly string $description,
        public readonly bool $featured = false,
    ) {}
}
