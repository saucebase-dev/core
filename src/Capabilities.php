<?php

namespace Saucebase\Core;

use Closure;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use LogicException;

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
    /**
     * By group, then by where `add()` was called (`file:line`), so a declaration that runs
     * again replaces itself.
     *
     * @var array<string, array<string, array<string, array<string, mixed>>|Closure(): array<string, array<string, mixed>>>>
     */
    private array $entries = [];

    private bool $loaded = false;

    public function __construct(private readonly ModuleRegistry $modules) {}

    /** @param  array<string, array<string, mixed>>|Closure(): array<string, array<string, mixed>>  $entries  key => ['label' => …, 'description' => …] */
    public function add(string $group, array|Closure $entries): void
    {
        $this->entries[$group][self::caller()] = $entries;
    }

    /**
     * Where `add()` was called from, past the facade. Modular also requires a module's
     * `routes/*.php` at boot, so without a route cache a capabilities file runs twice:
     * keyed by its call site, the repeat is the same declaration, not a second one.
     */
    private static function caller(): string
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6) as $frame) {
            $file = $frame['file'] ?? null;

            if ($file !== null && $file !== __FILE__ && ! str_ends_with($file, 'Facades/Facade.php')) {
                return $file.':'.($frame['line'] ?? 0);
            }
        }

        return 'unknown';
    }

    /**
     * Every entry in the group, labels translated (the key when there is none).
     * Keys besides label and description (`read_only`) pass through for the
     * group's reader. A key declared by two sources in one group throws; across groups it is fine.
     *
     * @return array<string, array<string, mixed>>
     */
    public function group(string $group): array
    {
        $this->load();

        $all = [];

        foreach ($this->entries[$group] ?? [] as $entries) {
            foreach ($entries instanceof Closure ? $entries() : $entries as $key => $entry) {
                // Two sources would leave one silently replacing the other, even when they agree.
                if (array_key_exists($key, $all)) {
                    throw new LogicException("The capability [{$key}] is declared twice in the [{$group}] group.");
                }

                $all[$key] = $entry;
            }
        }

        return collect($all)
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
