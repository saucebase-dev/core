<?php

namespace Saucebase\Core\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Saucebase\Core\Console\Traits\InstallsSaucebasePackages;
use Saucebase\Core\ProjectConfig;
use Saucebase\Core\Support\DependencyOrder;
use Symfony\Component\Finder\Finder;

/**
 * Turns a fresh application into a template: requires it (with its modules),
 * copies its stubs over the app, then sets everything up; --state seeds demo content.
 * The copied files belong to the app; updating the template never touches them.
 */
class TemplateInstallCommand extends Command
{
    use InstallsSaucebasePackages;

    protected $signature = 'saucebase:template-install
                            {template : Template name (uptime)}
                            {--state= : Seed one of the template\'s states, such as default (its demo); without it the app starts clean}
                            {--force : Seed in production without asking}
                            {--set=* : Configure a module, module.key=value (tenancy.mode=subdomain, auth.registration_enabled=false)}';

    protected $description = 'Install a Saucebase template into a fresh application';

    private const FRAMEWORKS = ['vue', 'react'];

    /** Files Composer and the installer own; a template never replaces them. */
    private const REFUSED = ['composer.json', 'composer.lock'];

    public function handle(Filesystem $files, ProjectConfig $config): int
    {
        $template = $this->argument('template');

        if ($config->isContributor()) {
            return $this->refuse('Templates install into an application, not a Saucebase checkout. Use a sandbox app.');
        }

        $other = collect($this->lockedPackages())
            ->where('type', 'saucebase-template')
            ->keys()
            ->reject(fn (string $name) => $name === $template)
            ->first();

        if ($other !== null) {
            return $this->refuse("This app already has the {$other} template. An app takes one template, installed fresh.");
        }

        // Only a seeded state creates accounts; a clean install needs no password.
        if ($this->option('state') && blank(config('saucebase.demo_password'))) {
            return $this->refuse('Set DEMO_PASSWORD in .env. Every account the template seeds uses it.');
        }

        if (! $this->requirePackages(["saucebase/{$template}"])) {
            return $this->refuse('Composer could not install the template. Nothing else was changed.');
        }

        if (($this->lockedPackages()[$template]['type'] ?? null) !== 'saucebase-template') {
            return $this->refuse("saucebase/{$template} is not a template. Install modules with saucebase:module-install.");
        }

        $copies = $this->stubs($files, base_path("modules/{$template}/stubs"), $config->getFramework());

        if (is_string($copies)) {
            return $this->retry($copies);
        }

        foreach ($copies as $target => $source) {
            $files->ensureDirectoryExists(dirname(base_path($target)));
            $files->copy($source, base_path($target));
        }

        $this->addMissingEnvKeys($files, $template);

        // The template and everything it depends on, not just what Composer added this run:
        // on a retry Composer adds nothing, and a module whose setup failed must still run.
        $after = $this->lockedModules();
        $modules = DependencyOrder::sort($this->closure($template, $after), $after);

        $force = (bool) $this->option('force');

        if (! $this->runSetup($modules, null, $force)) {
            return $this->retry('Setting up the template failed.');
        }

        // Last, in a process of its own: the state sees the app as every module's install
        // command left it (tenancy's address mode, for one, decides where a seeded site lives).
        $state = $this->state();

        if ($state !== null && ! $this->seedState($modules, $state, $force)) {
            return $this->retry('Seeding the template\'s state failed.');
        }

        return self::SUCCESS;
    }

    /**
     * Every stub's target, checked before anything is written.
     *
     * @return array<string, string>|string Target path (relative to the app) => source file, or why the stubs are invalid.
     */
    private function stubs(Filesystem $files, string $root, ?string $framework): array|string
    {
        if (! $files->isDirectory($root)) {
            return [];
        }

        $copies = [];
        $frameworks = [];

        foreach (Finder::create()->in($root)->ignoreDotFiles(false)->ignoreVCS(true) as $file) {
            $path = str_replace('\\', '/', $file->getRelativePathname());

            if ($file->isLink()) {
                return "stubs/{$path} is a symlink. Stubs must be real files.";
            }

            if ($file->isDir()) {
                continue;
            }

            // resources/js/vue/pages/Index.vue → resources/js/pages/Index.vue
            if (preg_match('#^resources/(js|views)/('.implode('|', self::FRAMEWORKS).')/(.+)$#', $path, $match)) {
                $frameworks[$match[2]] = true;

                if ($match[2] !== $framework) {
                    continue;
                }

                $target = "resources/{$match[1]}/{$match[3]}";
            } else {
                $target = $path;
            }

            if (in_array($target, self::REFUSED, true) || ($target !== '.env.example' && Str::startsWith(basename($target), '.env'))) {
                return "stubs/{$path} would replace {$target}, which a template can't change.";
            }

            if (isset($copies[$target])) {
                return "Two stubs replace {$target}.";
            }

            $copies[$target] = $file->getPathname();
        }

        if ($frameworks !== [] && ! isset($frameworks[$framework])) {
            return "The template has no stubs for {$framework}.";
        }

        return $copies;
    }

    /**
     * The app's .env predates the template, so settings the template documents in
     * its .env.example are added to it: missing keys only, never a changed value.
     */
    private function addMissingEnvKeys(Filesystem $files, string $template): void
    {
        $example = base_path("modules/{$template}/stubs/.env.example");
        $env = base_path('.env');

        if (! $files->isFile($example) || ! $files->isFile($env)) {
            return;
        }

        $current = $files->get($env);
        $missing = array_diff_key(Dotenv::parse($files->get($example)), Dotenv::parse($current));

        if ($missing === []) {
            return;
        }

        $keys = array_keys($missing);
        $count = count($keys).' missing '.Str::plural('key', count($keys));

        // Without a person to ask (the installer), the example's defaults are added.
        if ($this->input->isInteractive() && ! $this->confirm("Add {$count} from the {$template} template to .env?", true)) {
            $this->components->warn('Without these keys the app may not work as expected: '.implode(', ', $keys)
                .". Copy them from .env.example, and check the {$template} template's documentation.");

            return;
        }

        $lines = $this->assignments($files->get($example), array_keys($missing));

        $files->put($env, rtrim($current, "\n")."\n\n# Added by saucebase:template-install ({$template}): defaults from the template's .env.example.\n"
            .implode("\n", $lines)."\n");

        $this->components->info('Added to .env: '.implode(', ', $keys).'.');
    }

    /**
     * The template and every module it requires, directly or through another module.
     *
     * @param  array<string, string[]>  $dependencies
     * @return list<string>
     */
    private function closure(string $template, array $dependencies): array
    {
        $found = [];
        $pending = [$template];

        while ($pending !== []) {
            $name = array_pop($pending);

            if (! in_array($name, $found, true)) {
                $found[] = $name;
                $pending = [...$pending, ...$dependencies[$name] ?? []];
            }
        }

        return $found;
    }

    /**
     * The example's own lines for these keys, so quoting stays as written. A quoted value
     * that spans lines is taken whole, up to its closing quote.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function assignments(string $example, array $keys): array
    {
        $taken = [];
        $closing = null;

        foreach (preg_split('/\R/', $example) ?: [] as $line) {
            if ($closing !== null) {
                $taken[] = $line;
                $closing = preg_match('/(?<!\\\\)'.$closing.'/', $line) ? null : $closing;

                continue;
            }

            if (preg_match('/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $match) && in_array($match[1], $keys, true)) {
                $taken[] = $line;
                $quote = $match[2][0] ?? '';
                // Opens a quote it doesn't close on this line: the value goes on.
                $closing = in_array($quote, ['"', "'"], true) && ! preg_match('/(?<!\\\\)'.$quote.'/', substr($match[2], 1)) ? $quote : null;
            }
        }

        return $taken;
    }

    /**
     * Only the state --state names. Demo content is asked for, never assumed (a real app
     * would have to delete it), so a plain install leaves the app clean.
     */
    private function state(): ?string
    {
        return $this->option('state') ?: null;
    }

    private function retry(string $error): int
    {
        $command = 'php artisan saucebase:template-install '.$this->argument('template')
            .($this->option('state') ? ' --state='.$this->option('state') : '')
            .collect((array) $this->option('set'))->map(fn (string $set) => ' --set='.escapeshellarg($set))->implode('');

        $this->components->error($error);
        $this->line("  Fix it, then run again: {$command}");

        return self::FAILURE;
    }

    private function refuse(string $error): int
    {
        $this->components->error($error);

        return self::FAILURE;
    }
}
