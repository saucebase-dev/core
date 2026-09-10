<?php

namespace Saucebase\Core\Tests\Fixtures\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saucebase\Core\Filament\Admin\GeneralSettings;
use Saucebase\Core\Filament\Admin\LocalizationSettings;
use Saucebase\Core\Filament\ModulesPlugin;

/**
 * A minimal stand-in for the application's AdminPanelProvider.
 *
 * Core ships admin pages but not the panel that hosts them — the application owns
 * that, along with its branding and access policy. Tests still need somewhere to
 * register the pages, so this mirrors only the parts they exercise: the two core
 * pages, the module plugin, and enough middleware for a session and a login.
 */
class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->pages([
                Dashboard::class,
                GeneralSettings::class,
                LocalizationSettings::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->plugins([ModulesPlugin::make()])
            ->default();
    }
}
