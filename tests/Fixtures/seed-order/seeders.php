<?php

// Two stand-in modules for SeedModulesCommandTest: alpha's seeders ask to run after omega's.

namespace Modules\Alpha\Database\Seeders;

use Illuminate\Database\Seeder;
use Saucebase\Core\Attributes\SeedAfter;
use Saucebase\Core\Tests\Feature\SeedModulesCommandTest;

#[SeedAfter('omega', 'missing')]
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SeedModulesCommandTest::$ran[] = 'alpha';
    }
}

#[SeedAfter('omega')]
class ShowcaseAlphaDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SeedModulesCommandTest::$ran[] = 'alpha showcase';
    }
}

namespace Modules\Omega\Database\Seeders;

use Illuminate\Database\Seeder;
use Saucebase\Core\Tests\Feature\SeedModulesCommandTest;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SeedModulesCommandTest::$ran[] = 'omega';
    }
}

class ShowcaseOmegaDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SeedModulesCommandTest::$ran[] = 'omega showcase';
    }
}

/** The `broken` state: fails, so the command must too. */
class BrokenOmegaDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        throw new \RuntimeException('broken seeder');
    }
}
