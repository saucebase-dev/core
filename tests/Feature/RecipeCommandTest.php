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
        // Core finds the setup in vue/ or react/ itself: no shim to rewrite per stack.
        $this->assertFileDoesNotExist($this->module('resources/js/app.ts'));
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

        $feature = $this->read('tests/Feature/IndexTest.php');
        $this->assertStringContainsString('namespace Modules\Notes\Tests\Feature;', $feature);
        $this->assertStringContainsString("->get('/notes')", $feature);
        $this->assertStringContainsString("->component('Notes::Index'", $feature);
    }

    public function test_it_offers_the_recipes_by_name(): void
    {
        $this->installedWith('vue');
        $this->files->ensureDirectoryExists($this->appPath.'/stubs/recipes/api-only');
        $this->files->put($this->appPath.'/stubs/recipes/api-only/recipe.json', json_encode(['name' => 'API only']));
        $this->files->put($this->appPath.'/stubs/recipes/api-only/composer.json', json_encode(['name' => '{COMPOSER_VENDOR}/{module-}']));

        $this->artisan('saucebase:recipe', ['module' => 'Notes'])
            ->expectsChoice('Which recipe would you like to use?', 'basic', ['basic' => 'Basic', 'template' => 'Template', 'api-only' => 'API only'])
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

    /** An app builds its template in place: the recipe sets everything a template needs. */
    public function test_the_template_recipe_makes_the_template_the_app_builds(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'template', '--vendor' => 'acme'])->assertSuccessful();

        $composer = json_decode($this->read('composer.json'), true);
        $this->assertSame('acme/notes', $composer['name']);
        $this->assertSame('saucebase-template', $composer['type']);
        $this->assertSame(['vue'], $composer['extra']['saucebase']['frameworks']);
        $this->assertArrayHasKey('saucebase/core', $composer['require']);

        // Published to other apps, so its files keep their stack folder; the other stack goes.
        $this->assertFileExists($this->module('resources/js/vue/pages/Index.vue'));
        $this->assertFileExists($this->module('resources/js/vue/app.ts'));
        $this->assertDirectoryDoesNotExist($this->module('resources/js/react'));
        $this->assertDirectoryDoesNotExist($this->module('resources/js/pages'));

        // Its demo is the default state.
        $this->assertFileExists($this->module('database/seeders/DefaultNotesDatabaseSeeder.php'));
        $this->assertStringContainsString('class DefaultNotesDatabaseSeeder', $this->read('database/seeders/DefaultNotesDatabaseSeeder.php'));
        $this->assertSame([], glob($this->module('database/seeders/Showcase*')));

        $this->assertStringContainsString('--template=acme/notes', $this->read('README.md'));
        $this->assertStringContainsString('saucebase-template-development', $this->read('resources/boost/skills/saucebase-notes-development/SKILL.md'));

        $this->assertSame('notes', json_decode($this->files->get($this->appPath.'/saucebase.json'), true)['template']);
    }

    /** Built on this app's modules, so it requires them: an app it installs into gets them too. */
    public function test_a_template_requires_the_modules_the_app_has(): void
    {
        $this->installedWith('vue');
        $this->files->put($this->appPath.'/composer.lock', json_encode(['packages' => [
            ['name' => 'saucebase/auth', 'type' => 'saucebase-module', 'version' => 'v3.2.1'],
            ['name' => 'saucebase/blog', 'type' => 'saucebase-module', 'version' => 'dev-dev'],
            ['name' => 'saucebase/core', 'type' => 'library', 'version' => 'v2.13.0'],
            ['name' => 'laravel/framework', 'type' => 'library', 'version' => 'v13.0.0'],
        ]]));

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'template', '--vendor' => 'acme'])->assertSuccessful();

        $this->assertSame(
            ['saucebase/core' => '^2.13', 'saucebase/auth' => '^3.2', 'saucebase/blog' => '*'],
            json_decode($this->read('composer.json'), true)['require'],
        );
    }

    public function test_an_app_builds_one_template(): void
    {
        $this->files->put($this->appPath.'/saucebase.json', json_encode(['framework' => 'vue', 'template' => 'shop']));

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'template'])
            ->expectsOutputToContain('already builds the shop template')
            ->assertFailed();

        $this->assertDirectoryDoesNotExist($this->appPath.'/modules/notes');
    }

    public function test_a_module_recipe_leaves_the_app_template_alone(): void
    {
        $this->installedWith('vue');

        $this->artisan('saucebase:recipe', ['module' => 'Notes', 'recipe' => 'basic'])->assertSuccessful();

        $this->assertArrayNotHasKey('template', json_decode($this->files->get($this->appPath.'/saucebase.json'), true));
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
