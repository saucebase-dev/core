<?php

namespace Saucebase\Core\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Schemas\Components\Utilities\Set;
use Saucebase\Core\Filament\Period;

/**
 * Filament's dashboard with a period filter: the header's filter button, top
 * right, opens a slide-over with presets and dates. Widgets read it with
 * `Concerns\ReadsPeriod`. Other dashboards extend this one.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label(fn (): string => $this->periodLabel())
                ->icon('heroicon-m-calendar')
                ->fillForm(fn (): array => $this->filtersWithDates($this->filters))
                ->schema([
                    Select::make('period')
                        ->label(__('Period'))
                        ->options(Period::options())
                        ->selectablePlaceholder(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state !== 'custom') {
                                $dates = $this->filtersWithDates(['period' => $state]);
                                $set('start', $dates['start']);
                                $set('end', $dates['end']);
                            }
                        }),
                    DatePicker::make('start')
                        ->label(__('From'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('period', 'custom')),
                    DatePicker::make('end')
                        ->label(__('To'))
                        ->required()
                        ->afterOrEqual('start')
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('period', 'custom')),
                ]),
        ];
    }

    /** The preset's name, or the custom range's dates. */
    private function periodLabel(): string
    {
        $preset = Period::preset($this->filters);
        $period = Period::fromFilters($this->filters);

        // A custom range without valid dates has fallen back to the default: say so.
        if ($preset !== 'custom' || ($period->end->isToday() && $period->start->equalTo(Period::fromFilters(null)->start))) {
            return Period::options()[$preset === 'custom' ? Period::DEFAULT : $preset];
        }

        return $period->start->translatedFormat('M j, Y').' – '.$period->end->translatedFormat('M j, Y');
    }

    /**
     * The saved filters with both dates filled in, so the form shows what a preset covers.
     *
     * @param  array<string, mixed>|null  $filters
     * @return array{period: string, start: string, end: string}
     */
    private function filtersWithDates(?array $filters): array
    {
        $period = Period::fromFilters($filters);

        return [
            'period' => Period::preset($filters),
            'start' => $period->start->toDateString(),
            'end' => $period->end->toDateString(),
        ];
    }
}
