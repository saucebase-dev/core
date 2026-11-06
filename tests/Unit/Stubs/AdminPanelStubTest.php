<?php

namespace Saucebase\Core\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;

/**
 * New apps start from these files: the admin dashboard is core's (with the period
 * filter), carries no Filament branding, and opens with the users widget.
 */
class AdminPanelStubTest extends TestCase
{
    private const string APP = __DIR__.'/../../../stubs/app/';

    public function test_the_panel_uses_cores_dashboard_without_filament_widgets(): void
    {
        $provider = (string) file_get_contents(self::APP.'app/Providers/Filament/AdminPanelProvider.php');

        $this->assertStringContainsString('use Saucebase\Core\Filament\Pages\Dashboard;', $provider);
        $this->assertStringNotContainsString('use Filament\Pages\Dashboard;', $provider);
        $this->assertStringNotContainsString('FilamentInfoWidget', $provider);
        $this->assertStringNotContainsString('AccountWidget', $provider);
    }

    public function test_the_app_ships_the_users_widget_and_its_test(): void
    {
        $this->assertFileExists(self::APP.'app/Filament/Admin/Widgets/UsersWidget.php');
        $this->assertFileExists(self::APP.'tests/Feature/AdminDashboardTest.php');
    }
}
