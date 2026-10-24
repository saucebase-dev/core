<?php

namespace Modules\ModuleTestFixture\Console\Commands;

use Illuminate\Console\Command;

/** The module's own install step, named in its composer.json `extra.saucebase.install`. */
class InstallFixtureCommand extends Command
{
    public static bool $ran = false;

    protected $signature = 'module-test-fixture:install';

    public function handle(): int
    {
        static::$ran = true;

        return self::SUCCESS;
    }
}
