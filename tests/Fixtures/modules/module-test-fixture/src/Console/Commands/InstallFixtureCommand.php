<?php

namespace Modules\ModuleTestFixture\Console\Commands;

use Illuminate\Console\Command;

/** The module's own install step, listed in its composer.json `extra.saucebase.post-install`. */
class InstallFixtureCommand extends Command
{
    public static bool $ran = false;

    public static ?string $mode = null;

    /** @var array<string, mixed> */
    public static array $options = [];

    protected $signature = 'module-test-fixture:install {--mode=} {--onboarding=} {--single}';

    public function handle(): int
    {
        static::$ran = true;
        static::$mode = $this->option('mode');
        static::$options = $this->options();

        return self::SUCCESS;
    }
}
