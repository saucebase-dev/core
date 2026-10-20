<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Saucebase\Core\Providers\ModuleServiceProvider;
use Saucebase\Core\Tests\TestCase;

class RecipeCommandTest extends TestCase
{
    private Filesystem $files;

    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->appPath = sys_get_temp_dir().'/saucebase-recipe-command-test-'.uniqid();

        $this->files->ensureDirectoryExists($this->appPath);
        $this->files->put($this->appPath.'/composer.json', json_encode(['name' => 'acme/app', 'require' => []]));

        $this->app->setBasePath($this->appPath);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->appPath);

        parent::tearDown();
    }

    public function test_it_creates_a_module_from_the_recipe_core_ships(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        // The recipe names core's classes, which move: a stale name breaks every new module.
        $this->assertStringContainsString('use '.ModuleServiceProvider::class.';', $this->read('src/Providers/NotesServiceProvider.php'));
        $this->assertStringContainsString('"type": "laravel-module"', $this->read('composer.json'));
        $this->assertStringContainsString('"name": "modules/notes"', $this->read('composer.json'));
        $this->assertSame(['name' => '@modules/notes', 'private' => true, 'type' => 'module'], json_decode($this->read('package.json'), true));

        // What describes the recipe is not part of the module.
        $this->assertFileDoesNotExist($this->module('recipe.json'));
    }

    public function test_an_installed_application_gets_only_its_own_framework(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $this->assertStringContainsString('export function setup()', $this->read('resources/js/app.ts'));
        $this->assertFileExists($this->module('resources/js/pages/Index.vue'));
        $this->assertDirectoryDoesNotExist($this->module('resources/js/vue'));
        $this->assertDirectoryDoesNotExist($this->module('resources/js/react'));
    }

    public function test_react_gets_its_own_entry_file(): void
    {
        $this->installedWith('react');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $this->assertStringContainsString('export function setup()', $this->read('resources/js/app.tsx'));
        $this->assertFileExists($this->module('resources/js/pages/Index.tsx'));
        $this->assertFileDoesNotExist($this->module('resources/js/app.ts'));
        $this->assertDirectoryDoesNotExist($this->module('resources/js/react'));
    }

    public function test_a_contributor_checkout_keeps_both_frameworks(): void
    {
        $this->installedWith('vue', contributor: true);

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $this->assertFileExists($this->module('resources/js/vue/app.ts'));
        $this->assertFileExists($this->module('resources/js/react/app.tsx'));
        $this->assertSame("export * from './vue/app';\n", $this->read('resources/js/app.ts'));
    }

    public function test_it_finds_the_recipes_the_application_adds(): void
    {
        $this->installedWith('vue');
        $this->files->ensureDirectoryExists($this->appPath.'/stubs/recipes/api-only/src');
        $this->files->put($this->appPath.'/stubs/recipes/api-only/recipe.json', json_encode(['name' => 'API only']));
        $this->files->put($this->appPath.'/stubs/recipes/api-only/composer.json', json_encode(['name' => '{COMPOSER_VENDOR}/{module-}']));
        $this->files->put($this->appPath.'/stubs/recipes/api-only/src/ModuleClient.php', '<?php // {Module}');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'api-only'])->assertSuccessful();

        $this->assertSame('<?php // Notes', $this->read('src/NotesClient.php'));
    }

    public function test_a_folder_without_a_composer_file_is_not_a_recipe(): void
    {
        $this->installedWith('vue');
        $this->files->ensureDirectoryExists($this->appPath.'/stubs/recipes/notes-only');
        $this->files->put($this->appPath.'/stubs/recipes/notes-only/README.md', 'Not a package.');

        // Composer could not install what it produced.
        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'notes-only', '--no-interaction' => true])
            ->assertFailed();
    }

    public function test_the_recipe_tests_the_page_it_creates(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $this->assertStringContainsString("page.goto('/notes')", $this->read('tests/e2e/index.spec.ts'));
        $this->assertStringContainsString("from '@e2e/fixtures'", $this->read('tests/e2e/index.spec.ts'));
    }

    public function test_it_offers_the_recipes_by_name(): void
    {
        $this->installedWith('vue');
        $this->files->ensureDirectoryExists($this->appPath.'/stubs/recipes/api-only');
        $this->files->put($this->appPath.'/stubs/recipes/api-only/recipe.json', json_encode(['name' => 'API only']));
        $this->files->put($this->appPath.'/stubs/recipes/api-only/composer.json', json_encode(['name' => '{COMPOSER_VENDOR}/{module-}']));

        $this->artisan('saucebase:recipe', ['module' => 'Notes'])
            ->expectsChoice('Which recipe would you like to use?', 'basic', ['basic' => 'Basic', 'api-only' => 'API only'])
            ->assertSuccessful();

        $this->assertFileExists($this->module('src/Providers/NotesServiceProvider.php'));
    }

    public function test_it_names_the_recipes_when_given_one_it_does_not_know(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'missing', '--no-interaction' => true])
            ->expectsOutputToContain('basic')
            ->assertFailed();

        $this->assertDirectoryDoesNotExist($this->appPath.'/modules/notes');
    }

    public function test_vendor_and_type_options_name_the_package(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic', '--vendor' => 'acme', '--type' => 'library'])
            ->assertSuccessful();

        $composer = json_decode($this->read('composer.json'), true);
        $this->assertSame('acme/notes', $composer['name']);
        $this->assertSame('library', $composer['type']);
    }

    public function test_the_configured_vendor_is_used_without_the_option(): void
    {
        $this->installedWith('vue');
        config(['app-modules.modules_vendor' => 'shop']);

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $this->assertSame('shop/notes', json_decode($this->read('composer.json'), true)['name']);
    }

    public function test_the_module_is_required_by_the_application(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $app = $this->files->get($this->appPath.'/composer.json');
        $this->assertSame('*', json_decode($app, true)['require']['modules/notes']);
        $this->assertStringContainsString("\"modules/notes\": \"*\"\n", $app, 'Pretty-printed with unescaped slashes');
        $this->assertStringEndsWith("}\n", $app);
    }

    public function test_the_module_is_registered_in_the_taskfile(): void
    {
        $this->installedWith('vue');
        $this->files->put($this->appPath.'/Taskfile.yml', "includes:\n    # ── END MODULES\n");

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $this->assertSame(
            "includes:\n    notes:\n        taskfile: ./modules/notes/Taskfile.yml\n        optional: true\n    # ── END MODULES\n",
            $this->files->get($this->appPath.'/Taskfile.yml'),
        );
    }

    public function test_an_existing_module_is_left_untouched(): void
    {
        $this->installedWith('vue');
        $this->files->ensureDirectoryExists($this->appPath.'/modules/notes');
        $this->files->put($this->module('keep.txt'), 'mine');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertFailed();

        $this->assertSame(['keep.txt'], array_map('basename', $this->files->files($this->appPath.'/modules/notes')));
    }

    public function test_the_temporary_folder_is_removed(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->appPath.'/saucebase-temp');
    }

    private function installedWith(string $framework, bool $contributor = false): void
    {
        $this->files->put($this->appPath.'/saucebase.json', json_encode(['framework' => $framework, 'contributor' => $contributor]));
    }

    private function module(string $path): string
    {
        return $this->appPath.'/modules/notes/'.$path;
    }

    private function read(string $path): string
    {
        return $this->files->get($this->module($path));
    }
}
