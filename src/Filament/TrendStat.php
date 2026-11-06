<?php

namespace Saucebase\Core\Filament;

use Closure;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * A dashboard stat with its change against the previous period and an
 * optional trend line, coloured by whether the change is good.
 */
final class TrendStat
{
    /**
     * @param  list<int|float>  $chart
     * @param  (Closure(int|float): string)|null  $format
     */
    public static function make(string $label, int|float $now, int|float $before, array $chart = [], bool $lowerIsBetter = false, ?Closure $format = null): Stat
    {
        $stat = Stat::make($label, $format ? $format($now) : number_format($now));

        if (array_filter($chart)) {
            $stat->chart($chart);
        }

        $change = Period::change($now, $before);

        if ($change === null) {
            return $stat->description(__('No data for the previous period'))->color('gray');
        }

        $good = $change == 0 ? null : ($change > 0) !== $lowerIsBetter;
        $color = match ($good) {
            true => 'success',
            false => 'danger',
            null => 'gray',
        };

        return $stat
            ->description(__(':change% vs previous period', ['change' => ($change > 0 ? '+' : '').$change]))
            ->descriptionIcon($change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
            ->color($color);
    }
}
