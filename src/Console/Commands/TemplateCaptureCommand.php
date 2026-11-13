<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Saucebase\Core\ProjectConfig;

/**
 * In a template workspace (a contributor checkout whose saucebase.json names a
 * template), overrides are edited in the app. This copies them into the template's
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

        if (! $config->isContributor() || $template === null) {
            return $this->refuse('Run this in a template workspace: a contributor checkout with "template" in saucebase.json.');
        }

        $stubs = base_path("modules/{$template}/stubs");

        $paths = $files->isDirectory($stubs)
            ? collect($files->allFiles($stubs, true))->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))
            : collect();

        // Named files and the stubs already there pass the same check, so a stub put in by hand can't leak either.
        foreach ([...$paths, ...$this->argument('files')] as $path) {
            $path = $this->normalize($path);

            if ($path === null || ($path !== '.env.example' && Str::is(self::OWN, $path))) {
                return $this->refuse(($path ?? 'A path outside the app').' belongs to the workspace, not the template.');
            }

            $paths->push($path);
        }

        $paths = $paths->unique()->sort()->values();
        $missing = $paths->reject(fn (string $path) => $files->isFile(base_path($path)));

        if ($missing->isNotEmpty()) {
            return $this->refuse('These stubs have no app file to copy: '.$missing->implode(', ').'. Restore the file, or delete the stub.');
        }

        $stale = $paths->filter(fn (string $path) => ! $files->isFile("{$stubs}/{$path}")
            || $files->get("{$stubs}/{$path}") !== $files->get(base_path($path)));

        if ($this->option('check')) {
            return $stale->isEmpty() ? self::SUCCESS : $this->refuse(
                "modules/{$template}/stubs is out of date: ".$stale->implode(', ').'. Run: php artisan saucebase:template-capture'
            );
        }

        foreach ($stale as $path) {
            $files->ensureDirectoryExists(dirname("{$stubs}/{$path}"));
            $files->copy(base_path($path), "{$stubs}/{$path}");
        }

        $this->components->info($stale->isEmpty() ? 'Stubs are up to date.' : "Updated {$stale->count()} stubs in modules/{$template}/stubs.");
        $this->components->bulletList($stale->all());

        return self::SUCCESS;
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
