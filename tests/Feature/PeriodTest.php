<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Saucebase\Core\Filament\Period;
use Saucebase\Core\Tests\TestCase;

class PeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-06 15:00:00');
    }

    public function test_defaults_to_the_last_30_days(): void
    {
        $period = Period::fromFilters(null);

        $this->assertSame('2026-10-08 00:00:00', $period->start->toDateTimeString());
        $this->assertSame('2026-11-06 23:59:59', $period->end->toDateTimeString());
    }

    public function test_reads_a_preset(): void
    {
        $period = Period::fromFilters(['period' => '7d']);

        $this->assertSame('2026-10-31 00:00:00', $period->start->toDateTimeString());
    }

    public function test_reads_a_custom_range(): void
    {
        $period = Period::fromFilters(['period' => 'custom', 'start' => '2026-01-01', 'end' => '2026-01-31']);

        $this->assertSame('2026-01-01 00:00:00', $period->start->toDateTimeString());
        $this->assertSame('2026-01-31 23:59:59', $period->end->toDateTimeString());
    }

    public function test_an_incomplete_custom_range_falls_back_to_the_default(): void
    {
        $period = Period::fromFilters(['period' => 'custom', 'start' => '2026-01-01']);

        $this->assertSame('2026-10-08 00:00:00', $period->start->toDateTimeString());
    }

    public function test_an_unknown_preset_falls_back_to_the_default(): void
    {
        $this->assertSame('2026-10-08 00:00:00', Period::fromFilters(['period' => 'nope'])->start->toDateTimeString());
    }

    /** Filters come from the URL and the session: anything that isn't a preset or a date falls back. */
    public function test_malformed_filters_fall_back_to_the_default(): void
    {
        foreach ([
            ['period' => ['x']],
            ['period' => 'custom', 'start' => 'garbage', 'end' => '2026-01-31'],
            ['period' => 'custom', 'start' => ['2026-01-01'], 'end' => '2026-01-31'],
            ['period' => 'custom', 'start' => '2026-01-01 00:00:00; drop', 'end' => '2026-01-31'],
            ['period' => 'custom', 'start' => '2026-02-30', 'end' => '2026-03-01'],
        ] as $filters) {
            $this->assertSame('2026-10-08 00:00:00', Period::fromFilters($filters)->start->toDateTimeString(), json_encode($filters));
        }
    }

    public function test_a_reversed_range_is_put_the_right_way_round(): void
    {
        $period = Period::fromFilters(['period' => 'custom', 'start' => '2026-01-31', 'end' => '2026-01-01']);

        $this->assertSame('2026-01-01 00:00:00', $period->start->toDateTimeString());
        $this->assertSame('2026-01-31 23:59:59', $period->end->toDateTimeString());
    }

    /** At most five years, counted back from the end: every bucket is a chart point and a query row. */
    public function test_a_custom_range_is_capped(): void
    {
        $period = Period::fromFilters(['period' => 'custom', 'start' => '0001-01-01', 'end' => '2026-01-31']);

        $this->assertSame('2021-02-01 00:00:00', $period->start->toDateTimeString());
        $this->assertLessThanOrEqual(61, count($period->labels()));
    }

    /** The previous period is the same length, ending just before this one starts. */
    public function test_the_previous_period_has_the_same_length(): void
    {
        $previous = Period::fromFilters(['period' => '7d'])->previous();

        $this->assertSame('2026-10-24 00:00:00', $previous->start->toDateTimeString());
        $this->assertSame('2026-10-30 23:59:59', $previous->end->toDateTimeString());
    }

    public function test_the_bucket_size_follows_the_length(): void
    {
        $this->assertSame('day', Period::fromFilters(['period' => '30d'])->unit());
        $this->assertSame('week', Period::fromFilters(['period' => '90d'])->unit());
        $this->assertSame('month', Period::fromFilters(['period' => '12m'])->unit());
    }

    public function test_change_is_a_percentage_or_null_without_a_baseline(): void
    {
        $this->assertSame(50.0, Period::change(150, 100));
        $this->assertSame(-25.0, Period::change(75, 100));
        $this->assertNull(Period::change(10, 0));
    }

    public function test_series_fills_every_bucket_in_order(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('amount');
            $table->timestamp('created_at');
        });
        DB::table('events')->insert([
            ['amount' => 5, 'created_at' => '2026-11-04 10:00:00'],
            ['amount' => 7, 'created_at' => '2026-11-04 22:00:00'],
            ['amount' => 1, 'created_at' => '2026-11-06 01:00:00'],
            ['amount' => 9, 'created_at' => '2026-10-01 01:00:00'], // before the period
        ]);

        $period = Period::fromFilters(['period' => '7d']);

        $this->assertSame([0, 0, 0, 0, 2, 0, 1], $period->series(DB::table('events')));
        $this->assertSame([0, 0, 0, 0, 12, 0, 1], $period->series(DB::table('events'), 'sum(amount)'));
        $this->assertCount(7, $period->labels());
    }

    public function test_weekly_and_monthly_series_add_up_the_days(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('created_at');
        });
        DB::table('events')->insert([
            ['created_at' => '2026-10-01 10:00:00'],
            ['created_at' => '2026-10-02 10:00:00'],
            ['created_at' => '2026-11-05 10:00:00'],
        ]);

        $monthly = Period::fromFilters(['period' => '12m'])->series(DB::table('events'));
        $weekly = Period::fromFilters(['period' => '90d'])->series(DB::table('events'));

        $this->assertSame(3, array_sum($monthly));
        $this->assertSame([2, 1], array_slice($monthly, -2));
        $this->assertSame(3, array_sum($weekly));
        $this->assertSame(1, end($weekly));
    }
}
