<?php

namespace Modules\ModuleTestFixture\Database\Seeders;

use Illuminate\Database\Seeder;

/** Records that it ran, so a test can assert which seeders ran and in what order. */
class DatabaseSeeder extends Seeder
{
    /** @var list<string> */
    public static array $ran = [];

    public function run(): void
    {
        self::$ran[] = class_basename(static::class);
    }
}
