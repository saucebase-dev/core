<?php

namespace Saucebase\Core\Filament;

use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureComponentDefaults();
    }

    /**
     * How Filament components look before anybody asks.
     *
     * A toggle that is on is reporting a state, not offering the primary action on the
     * page, and `primary` is the brand colour every button already wears. Green says
     * "enabled" without competing with them.
     *
     * Three things can still override this, in order: an application's own
     * `configureUsing()` (app providers register after package providers, and the last
     * closure to set the colour wins), and any component naming its own `onColor()`.
     * `configureUsing` runs at `make()`, so this is a default, not a decision.
     *
     * Registered here rather than in a panel provider because these closures are
     * recorded against the component class and apply to every panel regardless of where
     * they are declared — putting them in one panel would only disguise that.
     */
    protected function configureComponentDefaults(): void
    {
        Toggle::configureUsing(fn (Toggle $toggle) => $toggle->onColor('success'));

        ToggleColumn::configureUsing(fn (ToggleColumn $column) => $column->onColor('success'));
    }
}
