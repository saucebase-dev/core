<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;

/**
 * Makes installed modules' AI guidelines and skills visible to Laravel Boost.
 *
 * Boost reads a package's `resources/boost` from `<vendor-dir>/<package>` and only for
 * packages listed in boost.json's `packages`. Modules install into `modules/<name>`, so
 * each one with a `resources/boost` directory gets a symlink at the path Boost expects
 * and an entry in `packages`.
 *
 * Core itself is a regular Composer package, so it needs no link, only the entry; a
 * stale entry is harmless because Boost ignores listed packages that aren't installed.
 *
 * What the command manages is recorded under boost.json's `saucebase.managed` — the link
 * it created or adopted and the target it pointed at — so a later run can remove exactly
 * that, and nothing a person put there.
 */
class SyncModuleBoostCommand extends Command
{
    protected $signature = 'modules:boost';

    protected $description = 'Register installed modules\' AI guidelines and skills with Laravel Boost';

    public function handle(ModuleRegistry $registry): int
    {
        $configPath = $this->laravel->basePath('boost.json');

        if (! file_exists($configPath)) {
            $this->components->info('No boost.json found; Laravel Boost is not installed.');

            return self::SUCCESS;
        }

        $original = (string) file_get_contents($configPath);
        $config = json_decode($original, true);

        if (! is_array($config) || ! $this->isValid($config)) {
            $this->components->error('boost.json is malformed; nothing was changed.');

            return self::FAILURE;
        }

        /** @var array<string, array{link: string, target: string}> $previous */
        $previous = $config['saucebase']['managed'] ?? [];
        $vendorDir = $this->vendorDir();
        $managed = [];
        $collisions = [];

        foreach ($registry->modules() as $module) {
            $package = $this->packageName($module);

            if ($package === null || ! is_dir($module->path('resources/boost'))) {
                continue;
            }

            $link = $vendorDir.'/'.$package;
            $bridge = $this->bridge($link, $module->base_path);

            if ($bridge === null) {
                $collisions[] = $package;
                $this->components->warn("{$package}: {$link} already exists and is not a link to the module; skipped.");

                continue;
            }

            $managed[$package] = ['link' => $this->storedPath($link), 'target' => $bridge];
        }

        foreach ($previous as $package => $entry) {
            if (($managed[$package]['link'] ?? null) === $entry['link']) {
                continue;
            }

            $link = $this->absolutePath($entry['link']);

            if (is_link($link) && readlink($link) === $entry['target']) {
                unlink($link);
            }
        }

        ksort($managed);

        $packages = collect($config['packages'] ?? [])
            ->diff(array_keys($previous))
            ->diff($collisions)
            ->merge(array_keys($managed))
            ->when(is_dir($vendorDir.'/saucebase/core/resources/boost'), fn ($packages) => $packages->push('saucebase/core'))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $saucebase = $config['saucebase'] ?? [];
        $this->put($saucebase, 'managed', $managed);
        $this->put($config, 'saucebase', $saucebase);
        $this->put($config, 'packages', $packages);
        ksort($config);

        $json = json_encode($config === [] ? (object) [] : $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

        if ($json !== $original) {
            file_put_contents($configPath, $json);
        }

        $this->components->info(count($managed).' module(s) registered with Boost.');

        return self::SUCCESS;
    }

    /**
     * Ensure `$link` points at the module, and return the target it points with.
     *
     * An existing link that already resolves to the module is adopted as-is; anything
     * else already at that path is someone else's, so null is returned and it is left
     * untouched.
     */
    private function bridge(string $link, string $modulePath): ?string
    {
        if (is_link($link)) {
            return realpath($link) === realpath($modulePath) ? (string) readlink($link) : null;
        }

        if (file_exists($link)) {
            return null;
        }

        $target = $this->relativePath(dirname($link), $modulePath);

        if (! is_dir(dirname($link))) {
            mkdir(dirname($link), 0777, true);
        }

        symlink($target, $link);

        return $target;
    }

    /**
     * The vendor directory, resolved the way Laravel Roster (Boost's package scanner) does.
     */
    private function vendorDir(): string
    {
        $composer = json_decode((string) @file_get_contents($this->laravel->basePath('composer.json')), true);
        $vendorDir = $composer['config']['vendor-dir'] ?? null;

        return $this->absolutePath(is_string($vendorDir) ? rtrim($vendorDir, '/') : 'vendor');
    }

    private function packageName(ModuleConfig $module): ?string
    {
        $manifest = json_decode((string) @file_get_contents($module->path('composer.json')), true);

        return is_string($manifest['name'] ?? null) ? $manifest['name'] : null;
    }

    /**
     * @param  array<mixed>  $config
     */
    private function isValid(array $config): bool
    {
        $packages = $config['packages'] ?? [];

        if (! is_array($packages) || ! array_is_list($packages) || array_filter($packages, 'is_string') !== $packages) {
            return false;
        }

        $saucebase = $config['saucebase'] ?? [];

        if (! is_array($saucebase)) {
            return false;
        }

        $managed = $saucebase['managed'] ?? [];

        if (! is_array($managed) || ($managed !== [] && array_is_list($managed))) {
            return false;
        }

        foreach ($managed as $entry) {
            if (! is_array($entry) || ! is_string($entry['link'] ?? null) || ! is_string($entry['target'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set a key, or drop it when empty — matching how Boost itself writes boost.json.
     *
     * @param  array<string, mixed>  $config
     * @param  array<mixed>  $value
     */
    private function put(array &$config, string $key, array $value): void
    {
        if ($value === []) {
            unset($config[$key]);

            return;
        }

        $config[$key] = $value;
    }

    private function absolutePath(string $path): string
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
            ? $path
            : $this->laravel->basePath($path);
    }

    /**
     * Relative to the base path when inside it, so boost.json stays portable.
     */
    private function storedPath(string $path): string
    {
        $base = rtrim($this->laravel->basePath(), '/').'/';

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    private function relativePath(string $from, string $to): string
    {
        $from = explode('/', trim(str_replace('\\', '/', $from), '/'));
        $to = explode('/', trim(str_replace('\\', '/', $to), '/'));

        while ($from !== [] && $to !== [] && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return implode('/', [...array_fill(0, count($from), '..'), ...$to]);
    }
}
