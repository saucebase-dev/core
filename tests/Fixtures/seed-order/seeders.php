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
class DemoAlphaDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SeedModulesCommandTest::$ran[] = 'alpha demo';
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

class DemoOmegaDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SeedModulesCommandTest::$ran[] = 'omega demo';
    }
}
