<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Saucebase\Core\ProjectConfig;

/**
 * In an app that builds a template (saucebase.json names it: an installed app, or a
 * contributor checkout), overrides are edited in the app. This copies them into the template's
 * stubs/, which saucebase:template-install later copies into customers' apps.
 * stubs/ is the list: only its files are refreshed, so module patches and other
 * app changes stay out. A file joins the list when named.
 */
class TemplateCaptureCommand extends Command
{
    protected $signature = 'saucebase:template-capture
                            {files?* : App files to add to the stubs (routes/web.php)}
                            {--check : Fail if stubs/ is out of date, without writing}';

    protected $description = 'Copy the app files a template overrides into its stubs';

    /** Files the workspace itself owns: never part of a template. */
    private const OWN = ['modules/*', 'vendor/*', 'saucebase.json', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', '.env*'];

    public function handle(Filesystem $files, ProjectConfig $config): int
    {
        $template = $config->template();

        if ($template === null) {
            return $this->refuse('Run this in an app that builds a template: "template" in saucebase.json.');
        }

        // An installed app's frontend is flat; the stubs keep the stack folder install drops.
        // A workspace keeps resources/js/<stack> as the stubs do, but its view is flat too.
        $stack = ['js' => $config->isContributor() ? null : $config->getFramework(), 'views' => $config->getFramework()];
        $stubs = base_path("modules/{$template}/stubs");

        $paths = $files->isDirectory($stubs)
            ? collect($files->allFiles($stubs, true))->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))
            : collect();

        // Named files and the stubs already there pass the same check, so a stub put in by hand can't leak either.
        foreach ([...$paths, ...array_map(fn (string $path) => $this->stubPath($this->normalize($path), $stack), $this->argument('files'))] as $path) {
            $path = $path === null ? null : $this->normalize($path);

            if ($path === null || ($path !== '.env.example' && Str::is(self::OWN, $path))) {
                return $this->refuse(($path ?? 'A path outside the app').' belongs to the workspace, not the template.');
            }

            $paths->push($path);
        }

        // Stub => the app file it comes from; another stack's stubs have none here.
        $sources = $paths->unique()->sort()->values()
            ->mapWithKeys(fn (string $path) => [$path => $this->appPath($path, $stack)])
            ->filter();

        $missing = $sources->reject(fn (string $app) => $files->isFile(base_path($app)));

        if ($missing->isNotEmpty()) {
            return $this->refuse('These stubs have no app file to copy: '.$missing->values()->implode(', ').'. Restore the file, or delete the stub.');
        }

        $stale = $sources->filter(fn (string $app, string $path) => ! $files->isFile("{$stubs}/{$path}")
            || $files->get("{$stubs}/{$path}") !== $files->get(base_path($app)))->keys();

        if ($this->option('check')) {
            return $stale->isEmpty() ? self::SUCCESS : $this->refuse(
                "modules/{$template}/stubs is out of date: ".$stale->implode(', ').'. Run: php artisan saucebase:template-capture'
            );
        }

        foreach ($stale as $path) {
            $files->ensureDirectoryExists(dirname("{$stubs}/{$path}"));
            $files->copy(base_path($sources[$path]), "{$stubs}/{$path}");
        }

        $this->components->info($stale->isEmpty() ? 'Stubs are up to date.' : "Updated {$stale->count()} stubs in modules/{$template}/stubs.");
        $this->components->bulletList($stale->all());

        return self::SUCCESS;
    }

    /**
     * resources/js/pages/Index.vue → resources/js/vue/pages/Index.vue, where that folder is flat.
     *
     * @param  array{js: ?string, views: ?string}  $stack  The stack each folder is flat for; null: kept as is.
     */
    private function stubPath(?string $path, array $stack): ?string
    {
        if ($path === null || ! preg_match('#^resources/(js|views)/(?!vue/|react/)(.+)$#', $path, $match) || $stack[$match[1]] === null) {
            return $path;
        }

        return "resources/{$match[1]}/{$stack[$match[1]]}/{$match[2]}";
    }

    /**
     * The app file a stub comes from; null for another stack's stub where the folder is flat.
     *
     * @param  array{js: ?string, views: ?string}  $stack
     */
    private function appPath(string $stub, array $stack): ?string
    {
        if (! preg_match('#^resources/(js|views)/(vue|react)/(.+)$#', $stub, $match) || $stack[$match[1]] === null) {
            return $stub;
        }

        return $match[2] === $stack[$match[1]] ? "resources/{$match[1]}/{$match[3]}" : null;
    }

    /** `./.env` and `routes/../.env` are `.env`: dot segments resolved, so no spelling slips past OWN. Null: outside the app. */
    private function normalize(string $path): ?string
    {
        $parts = [];

        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                if ($parts === []) {
                    return null;
                }

                array_pop($parts);

                continue;
            }

            $parts[] = $part;
        }

        return $parts === [] ? null : implode('/', $parts);
    }

    private function refuse(string $error): int
    {
        $this->components->error($error);

        return self::FAILURE;
    }
}
