<?php

namespace Saucebase\Core\Filament;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * The date range a dashboard is filtered to, read from its filters form
 * (`period`, plus `start` and `end` for a custom range). Presets include today:
 * "7 days" is today and the six before it.
 */
final readonly class Period
{
    public const DEFAULT = '30d';

    private function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'today' => __('Today'),
            '7d' => __('Last 7 days'),
            '30d' => __('Last 30 days'),
            '90d' => __('Last 3 months'),
            '12m' => __('Last 12 months'),
            'custom' => __('Custom range'),
        ];
    }

    /** The longest custom range, counted back from its end: every bucket is a chart point and a query row. */
    public const MAX_YEARS = 5;

    /**
     * Filters come from the URL and the session, so anything that isn't a known preset or
     * a real `Y-m-d` date falls back to the default rather than failing the page.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public static function fromFilters(?array $filters): self
    {
        $preset = self::preset($filters);
        $today = CarbonImmutable::today();

        if ($preset === 'custom' && ($start = self::date($filters['start'] ?? null)) && ($end = self::date($filters['end'] ?? null))) {
            [$start, $end] = $start <= $end ? [$start, $end] : [$end, $start];

            return new self(max($start, $end->subYears(self::MAX_YEARS)->addDay()), $end->endOfDay());
        }

        $start = match ($preset) {
            'today' => $today,
            '7d' => $today->subDays(6),
            '90d' => $today->subDays(89),
            '12m' => $today->subMonths(11)->startOfMonth(),
            default => $today->subDays(29),
        };

        return new self($start, $today->endOfDay());
    }

    /**
     * The preset the filters name, if it is one; else the default.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public static function preset(?array $filters): string
    {
        $preset = $filters['period'] ?? null;

        return is_string($preset) && isset(self::options()[$preset]) ? $preset : self::DEFAULT;
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $date : null; // 2026-02-30 rolls over: refuse it
    }

    /** The same number of days, ending the day before this one starts. */
    public function previous(): self
    {
        $days = $this->days();

        return new self($this->start->subDays($days), $this->start->subDay()->endOfDay());
    }

    /** @return 'day'|'week'|'month' */
    public function unit(): string
    {
        return match (true) {
            $this->days() <= 31 => 'day',
            $this->days() <= 92 => 'week',
            default => 'month',
        };
    }

    /** Percentage change from `$before` to `$now`; null when there is nothing to compare with. */
    public static function change(int|float $now, int|float $before): ?float
    {
        return $before == 0 ? null : round(($now - $before) / $before * 100, 1);
    }

    /**
     * One value per bucket, oldest first, empty buckets as 0. The database
     * groups by day (`date()` is the same in SQLite, MySQL and PostgreSQL);
     * days are added up into weeks or months here.
     *
     * @return list<int|float>
     */
    public function series(Builder $query, string $aggregate = 'count(*)', string $column = 'created_at'): array
    {
        $buckets = array_fill_keys(array_keys($this->labels()), 0);

        $rows = $query->whereBetween($column, [$this->start, $this->end])
            ->selectRaw("date({$column}) as bucket_day, {$aggregate} as bucket_value")
            ->groupBy('bucket_day')
            ->pluck('bucket_value', 'bucket_day');

        foreach ($rows as $day => $value) {
            $buckets[$this->bucketOf(CarbonImmutable::parse($day))] += 0 + $value;
        }

        return array_values($buckets);
    }

    /** @return array<string, string> bucket key => label */
    public function labels(): array
    {
        $labels = [];
        $cursor = $this->bucketStart($this->start);

        while ($cursor <= $this->end) {
            $labels[$this->bucketOf($cursor)] = $cursor->translatedFormat($this->unit() === 'month' ? 'M Y' : 'M j');
            $cursor = $cursor->add(1, $this->unit());
        }

        return $labels;
    }

    private function bucketOf(CarbonImmutable $day): string
    {
        return $this->bucketStart($day)->format('Y-m-d');
    }

    private function bucketStart(CarbonImmutable $day): CarbonImmutable
    {
        return match ($this->unit()) {
            'day' => $day->startOfDay(),
            'week' => $day->startOfWeek(),
            'month' => $day->startOfMonth(),
        };
    }

    private function days(): int
    {
        return (int) $this->start->diffInDays($this->end->addSecond());
    }
}
