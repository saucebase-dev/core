<?php

namespace Saucebase\Core;

use Closure;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;

/**
 * One catalog of what can be granted, grouped by who reads it: `api` for what a
 * token may be scoped to, `billing` for what a product may include. It only lists;
 * each group's reader decides how its entries are checked.
 *
 * The app and each module declare theirs in `routes/capabilities.php`, like
 * navigation, so an uninstalled module offers nothing:
 *
 *     Capabilities::add('api', ['posts' => ['label' => 'Blog posts', 'description' => '…', 'read_only' => true]]);
 *     Capabilities::add('billing', fn () => [...]); // from data: runs only when the group is read
 */
class Capabilities
{
    /** @var array<string, list<array<string, array<string, mixed>>|Closure(): array<string, array<string, mixed>>>> */
    private array $entries = [];

    private bool $loaded = false;

    public function __construct(private readonly ModuleRegistry $modules) {}

    /** @param  array<string, array<string, mixed>>|Closure(): array<string, array<string, mixed>>  $entries  key => ['label' => …, 'description' => …] */
    public function add(string $group, array|Closure $entries): void
    {
        $this->entries[$group][] = $entries;
    }

    /**
     * Every entry in the group, labels translated (the key when there is none).
     * Keys besides label and description (`read_only`) pass through for the
     * group's reader.
     *
     * @return array<string, array<string, mixed>>
     */
    public function group(string $group): array
    {
        $this->load();

        return collect($this->entries[$group] ?? [])
            ->flatMap(fn (array|Closure $entries): array => $entries instanceof Closure ? $entries() : $entries)
            ->map(fn (array $entry, string $key): array => [
                ...$entry,
                'label' => __($entry['label'] ?? $key),
                'description' => __($entry['description'] ?? ''),
            ])
            ->all();
    }

    /**
     * The app's file, then each module's. `require`, not `require_once`: a new
     * application (each test) loads them again; the flag keeps it to once per instance.
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        $files = [base_path('routes/capabilities.php'), ...$this->modules->modules()
            ->map(fn (ModuleConfig $module): string => $module->path('routes/capabilities.php'))
            ->all()];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require $file;
            }
        }
    }
}
