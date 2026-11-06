<?php

namespace Saucebase\Core\Filament\Concerns;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Saucebase\Core\Filament\Period;

/** For a widget on a `Saucebase\Core\Filament\Pages\Dashboard`: the period its picker is set to. */
trait ReadsPeriod /** @phpstan-ignore trait.unused */
{
    use InteractsWithPageFilters;

    protected function period(): Period
    {
        return Period::fromFilters($this->pageFilters);
    }
}
