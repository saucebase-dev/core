<?php

namespace Modules\ModuleTestFixture\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoModuleTestFixtureDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::$ran[] = class_basename(static::class);
    }
}
