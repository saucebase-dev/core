<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Saucebase\Core\Filament\Pages\Dashboard;
use Saucebase\Core\Filament\Period;
use Saucebase\Core\Tests\Fixtures\Filament\PeriodWidget;
use Saucebase\Core\Tests\Fixtures\User;
use Saucebase\Core\Tests\TestCase;

class DashboardPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-06 15:00:00');
        $this->actingAs(User::factory()->create());
    }

    /** Filament's filter action: a header button, top right, opening a slide-over. */
    public function test_the_period_is_picked_from_the_header(): void
    {
        Livewire::test(Dashboard::class)
            ->assertActionExists('filter', fn ($action): bool => $action->isModalSlideOver() && $action->getLabel() === 'Last 30 days')
            ->mountAction('filter')
            ->assertSchemaStateSet(['period' => Period::DEFAULT, 'start' => '2026-10-08', 'end' => '2026-11-06']);
    }

    public function test_a_preset_fills_the_dates(): void
    {
        Livewire::test(Dashboard::class)
            ->mountAction('filter')
            ->fillForm(['period' => '7d'])
            ->assertSchemaStateSet(['start' => '2026-10-31', 'end' => '2026-11-06'])
            ->callMountedAction()
            ->assertSet('filters.period', '7d')
            ->assertActionExists('filter', fn ($action): bool => $action->getLabel() === 'Last 7 days');
    }

    public function test_changing_a_date_makes_the_range_custom(): void
    {
        Livewire::test(Dashboard::class)
            ->mountAction('filter')
            ->fillForm(['start' => '2026-01-01', 'end' => '2026-01-31'])
            ->assertSchemaStateSet(['period' => 'custom'])
            ->callMountedAction()
            ->assertSet('filters', ['period' => 'custom', 'start' => '2026-01-01', 'end' => '2026-01-31'])
            ->assertActionExists('filter', fn ($action): bool => $action->getLabel() === 'Jan 1, 2026 – Jan 31, 2026');
    }

    public function test_the_end_cannot_come_before_the_start(): void
    {
        Livewire::test(Dashboard::class)
            ->mountAction('filter')
            ->fillForm(['period' => 'custom', 'start' => '2026-02-01', 'end' => '2026-01-01'])
            ->callMountedAction()
            ->assertHasFormErrors(['end']);
    }

    /** A crafted link must not break the page, nor the session it is saved in. */
    public function test_malformed_filters_in_the_url_fall_back(): void
    {
        foreach ([['period' => ['x']], ['period' => 'custom', 'start' => 'garbage', 'end' => '2026-01-01']] as $filters) {
            Livewire::withQueryParams(['filters' => $filters])
                ->test(Dashboard::class)
                ->assertOk()
                ->assertActionExists('filter', fn ($action): bool => $action->getLabel() === 'Last 30 days');
        }
    }

    public function test_a_widget_reads_the_period_from_the_page(): void
    {
        $widget = new PeriodWidget;

        $widget->pageFilters = ['period' => '7d'];
        $this->assertSame('2026-10-31', $widget->start());

        $widget->pageFilters = null;
        $this->assertSame('2026-10-08', $widget->start());
    }
}
