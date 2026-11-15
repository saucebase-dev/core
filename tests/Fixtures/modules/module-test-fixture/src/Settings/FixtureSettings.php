<?php

namespace Modules\ModuleTestFixture\Settings;

use Modules\ModuleTestFixture\Enums\FixtureStatus;
use Saucebase\Core\Settings\InstallSetting;
use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\SettingsCasts\EnumCast;

class FixtureSettings extends Settings
{
    #[InstallSetting('Let people sign up?')]
    public bool $signups;

    #[InstallSetting('Days to keep', rules: ['between:1,365'])]
    public int $days;

    #[InstallSetting('Status')]
    public FixtureStatus $status;

    public string $secret;

    public static function group(): string
    {
        return 'module-test-fixture';
    }

    /** @return array<string, string> */
    public static function casts(): array
    {
        return ['status' => EnumCast::class.':'.FixtureStatus::class];
    }
}
