<?php

namespace Saucebase\Core\Attributes;

use Attribute;

/**
 * Runs a module's seeder after the named modules' seeders of the same kind
 * (required or demo), for data that builds on theirs. Modules that aren't
 * installed are ignored.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class SeedAfter
{
    /** @var list<string> */
    public readonly array $modules;

    public function __construct(string ...$modules)
    {
        $this->modules = array_values($modules);
    }
}
