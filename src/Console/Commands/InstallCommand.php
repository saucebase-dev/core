<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Saucebase\Core\Console\Traits\DisplaysBanner;
use Saucebase\Core\ProjectConfig;
use stdClass;

/**
 * Turns a new Laravel application into a Saucebase one.
 *
 * Everything copied here becomes the application's own: the starter pages and
 * components, the build configuration, and the few PHP files Saucebase needs the
 * application to hold (the user model, the routes, the admin panel). Core keeps the
 * library, which stays in vendor/ and is imported from there.
 */
class InstallCommand extends Command
{
    use DisplaysBanner;

    protected $signature = 'saucebase:install
                            {framework? : The frontend framework to install (vue or react)}
                            {--force : Replace existing files without asking}';

    protected $description = 'Install Saucebase into this Laravel application';

    private const FRAMEWORKS = ['vue', 'react'];

    /** How the stack stubs name their own location, which an installed application must not keep. */
    private const CORE_STACK = 'vendor/saucebase/core/stubs/stack';

    /** What a build, a test run or `filament:assets` writes, which a repository should not hold. */
    private const GENERATED = [
        '/bootstrap/ssr',
        '/playwright-report',
        '/test-results',
        '/storage/framework/playwright',
        // Every env file, the E2E suite's `.env.e2e` included, but the examples.
        '.env.*',
        '!.env.*.example',
        '!.env.example',
        '/public/css/filament',
        '/public/js/filament',
        '/public/fonts/filament',
    ];

    public function handle(Filesystem $files, ProjectConfig $config): int
    {
        $this->displayBanner();

        if ($config->getFramework() !== null) {
            $this->components->error("Saucebase is already installed with {$config->getFramework()}.");

            return self::FAILURE;
        }

        $framework = $this->argument('framework') ?? $this->choice('Which frontend framework?', self::FRAMEWORKS, 'vue');

        if (! in_array($framework, self::FRAMEWORKS, true)) {
            $this->components->error("Unknown framework '{$framework}'. Choose one of: ".implode(', ', self::FRAMEWORKS).'.');

            return self::FAILURE;
        }

        $stubs = dirname(__DIR__, 3).'/stubs';

        if (! $this->option('force') && ! $this->confirmReplacing($files, $stubs.'/app')) {
            $this->components->error('Nothing was installed. Run again with --force to replace these files.');

            return self::FAILURE;
        }

        $files->copyDirectory($stubs.'/app', $this->laravel->basePath());
        $files->copyDirectory(dirname(__DIR__, 3).'/public', $this->laravel->publicPath());
        ServiceProvider::addProviderToBootstrapFile('App\Providers\Filament\AdminPanelProvider');
        $this->callSilently('filament:assets');
        $this->configureComposer($files);
        $this->ignoreGeneratedFiles($files);

        $this->publishStack($files, $stubs.'/stack', $framework);

        $this->components->info("Saucebase is installed with {$framework}.");
        $this->components->bulletList([
            'npm install && npm run build',
            'php artisan migrate --seed',
            'php artisan boost:install (pick your agent: it reads the Saucebase guidelines and skills from there)',
            'composer require saucebase/auth (sign-in pages: the dashboard and the admin panel need them)',
        ]);

        return self::SUCCESS;
    }

    /** Added to the application's own .gitignore, which keeps every line it already has. */
    private function ignoreGeneratedFiles(Filesystem $files): void
    {
        $path = $this->laravel->basePath('.gitignore');
        $current = $files->exists($path) ? $files->get($path) : '';
        $missing = array_diff(self::GENERATED, array_map(trim(...), explode("\n", $current)));

        if ($missing !== []) {
            $files->put($path, ($current === '' ? '' : rtrim($current, "\n")."\n\n")."# Saucebase\n".implode("\n", $missing)."\n");
        }
    }

    /** A new Laravel application already has some of these files, and they are about to be replaced. */
    private function confirmReplacing(Filesystem $files, string $stubs): bool
    {
        $existing = [];

        foreach ($files->allFiles($stubs) as $file) {
            if ($files->exists($this->laravel->basePath($file->getRelativePathname()))) {
                $existing[] = $file->getRelativePathname();
            }
        }

        if ($existing === []) {
            return true;
        }

        $this->components->warn('Installing replaces files this application already has:');
        $this->components->bulletList($existing);

        return $this->confirm('Replace these files?');
    }

    /**
     * Two things in the application's composer.json.
     *
     * Scripts for what a Composer run can leave stale: a Filament update brings new
     * admin panel assets, and a newly required module arrives without its generated types
     * or its Boost guidelines and skills.
     *
     * And the `modules/*` repository, so Composer finds a module the application wrote
     * itself. It is not canonical: a module that came from a registry and was copied
     * into `modules/` still gets its updates from that registry.
     */
    private function configureComposer(Filesystem $files): void
    {
        $path = $this->laravel->basePath('composer.json');

        if (! $files->exists($path)) {
            return;
        }

        // Decoded as objects, so an empty `{}` is not written back as `[]`.
        $composer = json_decode($files->get($path));
        $composer->scripts ??= new stdClass;
        $composer->scripts->{'post-autoload-dump'} = array_values(array_unique([
            ...(array) ($composer->scripts->{'post-autoload-dump'} ?? []),
            '@php artisan filament:upgrade',
            '@php artisan module:generate-types --all --no-interaction --ansi',
            '@php artisan modules:boost --ansi',
        ]));

        $repositories = (array) ($composer->repositories ?? []);
        $urls = array_column(json_decode((string) json_encode($repositories), true), 'url');

        if (! in_array('modules/*', $urls, true)) {
            $composer->repositories = [...array_values($repositories), [
                'type' => 'path',
                'url' => 'modules/*',
                'canonical' => false,
                'options' => ['symlink' => true],
            ]];
        }

        $files->put($path, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);
    }

    private function publishStack(Filesystem $files, string $stack, string $framework): void
    {
        $resources = $this->laravel->resourcePath();

        // Laravel's own entry file, which nothing builds once the framework's is there.
        $files->delete($resources.'/js/app.js');
        $files->copyDirectory("{$stack}/{$framework}/js", $resources.'/js');
        $files->copyDirectory("{$stack}/{$framework}/views", $resources.'/views');
        $files->copyDirectory("{$stack}/css", $resources.'/css');

        foreach ($files->files("{$stack}/{$framework}/config") as $file) {
            $files->put($this->laravel->basePath($file->getFilename()), str_replace(
                self::CORE_STACK."/{$framework}/js",
                'resources/js',
                $file->getContents(),
            ));
        }

        // Only a checkout of Saucebase itself has Tailwind scan core's copy of the UI.
        foreach ($files->allFiles($resources.'/css') as $file) {
            $files->put($file->getPathname(), preg_replace(
                '/^.*'.preg_quote(self::CORE_STACK, '/').'.*\R/m',
                '',
                $file->getContents(),
            ));
        }

        $path = $this->laravel->basePath('saucebase.json');
        $settings = $files->exists($path) ? (json_decode($files->get($path), true) ?? []) : [];

        $files->put($path, json_encode([...$settings, 'framework' => $framework], JSON_PRETTY_PRINT).PHP_EOL);
    }
}
