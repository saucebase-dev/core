<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Saucebase\Core\Filament\Pages\Dashboard;
use Saucebase\Core\Tests\Fixtures\User;
use Saucebase\Core\Tests\TestCase;

class DashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Widgets come from whichever modules are installed, so their sizes don't line up by
     * design: a half-width widget left alone on a row is filled by a later one that fits.
     */
    public function test_widgets_pack_into_gaps_left_by_other_widgets(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Dashboard::class)->assertSeeHtml('grid-auto-flow: row dense');
    }
}
