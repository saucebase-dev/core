<?php

namespace Saucebase\Core\Tests\Fixtures\Filament;

use Filament\Widgets\Widget;
use Saucebase\Core\Filament\Concerns\ReadsPeriod;

/** A widget on a period dashboard, exposing the start it reads. */
class PeriodWidget extends Widget
{
    use ReadsPeriod;

    public function start(): string
    {
        return $this->period()->start->toDateString();
    }
}
