<?php

namespace Saucebase\Core\Tests\Unit\Filament;

use Saucebase\Core\Filament\TrendStat;
use Saucebase\Core\Tests\TestCase;

class TrendStatTest extends TestCase
{
    public function test_a_rise_is_green_with_an_up_arrow(): void
    {
        $stat = TrendStat::make('Users', 150, 100, [1, 2, 3]);

        $this->assertSame('150', (string) $stat->getValue());
        $this->assertSame('+50% vs previous period', (string) $stat->getDescription());
        $this->assertSame('heroicon-m-arrow-trending-up', $stat->getDescriptionIcon());
        $this->assertSame('success', $stat->getColor());
        $this->assertSame([1, 2, 3], $stat->getChart());
    }

    public function test_a_fall_is_red_unless_lower_is_better(): void
    {
        $this->assertSame('danger', TrendStat::make('Users', 50, 100)->getColor());
        $this->assertSame('-50% vs previous period', (string) TrendStat::make('Users', 50, 100)->getDescription());
        $this->assertSame('success', TrendStat::make('Churn', 50, 100, lowerIsBetter: true)->getColor());
        $this->assertSame('heroicon-m-arrow-trending-down', TrendStat::make('Churn', 50, 100)->getDescriptionIcon());
    }

    public function test_no_baseline_says_so_instead_of_a_misleading_percentage(): void
    {
        $stat = TrendStat::make('Users', 3, 0);

        $this->assertSame('No data for the previous period', (string) $stat->getDescription());
        $this->assertSame('gray', $stat->getColor());
    }

    public function test_the_value_is_formatted(): void
    {
        $this->assertSame('1,234', (string) TrendStat::make('Users', 1234, 1000)->getValue());
        $this->assertSame('$12.34', (string) TrendStat::make('Revenue', 1234, 1000, format: fn ($v) => '$'.number_format($v / 100, 2))->getValue());
    }

    public function test_a_chart_with_no_movement_is_left_out(): void
    {
        $this->assertNull(TrendStat::make('Users', 0, 0, [0, 0, 0])->getChart());
    }
}
