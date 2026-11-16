<?php

namespace Modules\ModuleTestFixture\Database\Seeders;

use Illuminate\Database\Seeder;
use Saucebase\Core\Attributes\DemoAccount;

#[DemoAccount('fixture-admin@example.com', 'sees everything', featured: true)]
#[DemoAccount('fixture-staff@example.com', 'staff')]
class ShowcaseModuleTestFixtureDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::$ran[] = class_basename(static::class);
    }
}
