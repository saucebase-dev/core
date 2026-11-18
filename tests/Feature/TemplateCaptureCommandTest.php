<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Saucebase\Core\Tests\TestCase;

/**
 * `saucebase:template-capture` copies a template workspace's app files into the
 * template's stubs/. stubs/ is the list: only files already there are refreshed,
 * and a file joins it only when named.
 */
class TemplateCaptureCommandTest extends TestCase
{
    private Filesystem $files;

    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->appPath = sys_get_temp_dir().'/saucebase-template-capture-'.uniqid();
        $this->write('saucebase.json', '{"framework": "vue", "contributor": true, "template": "uptime"}');
        $this->write('modules/uptime/composer.json', '{}');
        $this->app->setBasePath($this->appPath);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->appPath);

        parent::tearDown();
    }

    public function test_stubs_are_refreshed_from_the_app(): void
    {
        $this->write('modules/uptime/stubs/routes/web.php', 'old');
        $this->write('modules/uptime/stubs/resources/js/vue/pages/Index.vue', 'old');
        $this->write('routes/web.php', 'template');
        $this->write('resources/js/vue/pages/Index.vue', 'vue');

        $this->artisan('saucebase:template-capture')->assertSuccessful();

        $this->assertSame('template', $this->stub('routes/web.php'));
        $this->assertSame('vue', $this->stub('resources/js/vue/pages/Index.vue'));
    }

    public function test_app_files_without_a_stub_are_left_out(): void
    {
        // A module's patch, not the template's.
        $this->write('app/Models/User.php', 'patched');
        $this->write('modules/uptime/stubs/routes/web.php', 'old');
        $this->write('routes/web.php', 'template');

        $this->artisan('saucebase:template-capture')->assertSuccessful();

        $this->assertFileDoesNotExist($this->appPath.'/modules/uptime/stubs/app/Models/User.php');
    }

    public function test_a_named_file_joins_the_stubs(): void
    {
        $this->write('routes/web.php', 'template');

        $this->artisan('saucebase:template-capture', ['files' => ['routes/web.php']])->assertSuccessful();

        $this->assertSame('template', $this->stub('routes/web.php'));
    }

    public function test_the_env_example_can_join(): void
    {
        $this->write('.env.example', 'UPTIME_TIMEOUT=10');

        $this->artisan('saucebase:template-capture', ['files' => ['.env.example']])->assertSuccessful();

        $this->assertSame('UPTIME_TIMEOUT=10', $this->stub('.env.example'));
    }

    public function test_files_the_workspace_owns_cannot_join(): void
    {
        $this->write('.env', 'APP_KEY=x');
        $this->write('composer.json', '{}');

        foreach (['.env', 'composer.json', 'modules/uptime/composer.json', '../outside.php', './.env', './composer.json', 'routes/./../.env', 'config//../.env'] as $file) {
            $this->artisan('saucebase:template-capture', ['files' => [$file]])->assertFailed();
        }

        $this->assertDirectoryDoesNotExist($this->appPath.'/modules/uptime/stubs');
    }

    /** A protected file already in stubs/ (put there by hand) is never refreshed from the app. */
    public function test_a_protected_stub_is_refused(): void
    {
        $this->write('.env', 'APP_KEY=secret');
        $this->write('modules/uptime/stubs/.env', 'old');

        $this->artisan('saucebase:template-capture')->assertFailed();

        $this->assertSame('old', $this->stub('.env'));
    }

    public function test_a_stub_whose_app_file_is_gone_fails(): void
    {
        $this->write('modules/uptime/stubs/routes/web.php', 'old');

        $this->artisan('saucebase:template-capture')->assertFailed();

        $this->assertSame('old', $this->stub('routes/web.php'));
    }

    public function test_check_fails_when_stubs_are_stale_and_writes_nothing(): void
    {
        $this->write('modules/uptime/stubs/routes/web.php', 'old');
        $this->write('routes/web.php', 'template');

        $this->artisan('saucebase:template-capture', ['--check' => true])->assertFailed();
        $this->assertSame('old', $this->stub('routes/web.php'));

        $this->artisan('saucebase:template-capture')->assertSuccessful();
        $this->artisan('saucebase:template-capture', ['--check' => true])->assertSuccessful();
    }

    public function test_it_needs_a_template_workspace(): void
    {
        $this->write('saucebase.json', '{"framework": "vue", "contributor": true}');

        $this->artisan('saucebase:template-capture')->assertFailed();
    }

    /** An installed app's frontend is flat; its stubs keep the stack folder install drops. */
    public function test_an_app_that_builds_a_template_files_frontend_stubs_under_its_stack(): void
    {
        $this->write('saucebase.json', '{"framework": "vue", "template": "uptime"}');
        $this->write('resources/js/pages/Index.vue', 'home');
        $this->write('resources/views/app.blade.php', 'view');
        $this->write('routes/web.php', 'routes');

        $this->artisan('saucebase:template-capture', ['files' => ['resources/js/pages/Index.vue', 'resources/views/app.blade.php', 'routes/web.php']])
            ->assertSuccessful();

        $this->assertSame('home', $this->stub('resources/js/vue/pages/Index.vue'));
        $this->assertSame('view', $this->stub('resources/views/vue/app.blade.php'));
        $this->assertSame('routes', $this->stub('routes/web.php'));
        $this->assertFileDoesNotExist($this->appPath.'/modules/uptime/stubs/resources/js/pages/Index.vue');

        // Refreshed from the same flat files, and checked against them.
        $this->write('resources/js/pages/Index.vue', 'new home');
        $this->artisan('saucebase:template-capture', ['--check' => true])->assertFailed();
        $this->artisan('saucebase:template-capture')->assertSuccessful();
        $this->assertSame('new home', $this->stub('resources/js/vue/pages/Index.vue'));
        $this->artisan('saucebase:template-capture', ['--check' => true])->assertSuccessful();
    }

    /** Another stack's stubs have no file in this app to come from: left as they are. */
    public function test_an_app_leaves_another_stacks_stubs_alone(): void
    {
        $this->write('saucebase.json', '{"framework": "vue", "template": "uptime"}');
        $this->write('modules/uptime/stubs/resources/js/react/pages/Index.tsx', 'react');

        $this->artisan('saucebase:template-capture', ['--check' => true])->assertSuccessful();
        $this->assertSame('react', $this->stub('resources/js/react/pages/Index.tsx'));
    }

    /**
     * A workspace keeps its stack's frontend under resources/js/<stack>, as the stubs do, but
     * Laravel loads its one view from resources/views: the view stubs keep the stack folder.
     */
    public function test_a_workspace_files_its_view_under_its_stack_and_leaves_the_other(): void
    {
        $this->write('resources/views/app.blade.php', 'vue view');
        $this->write('modules/uptime/stubs/resources/views/react/app.blade.php', 'react view');

        $this->artisan('saucebase:template-capture', ['files' => ['resources/views/app.blade.php']])->assertSuccessful();

        $this->assertSame('vue view', $this->stub('resources/views/vue/app.blade.php'));
        $this->assertSame('react view', $this->stub('resources/views/react/app.blade.php'));
        $this->assertFileDoesNotExist($this->appPath.'/modules/uptime/stubs/resources/views/app.blade.php');
        $this->artisan('saucebase:template-capture', ['--check' => true])->assertSuccessful();
    }

    private function write(string $path, string $contents): void
    {
        $this->files->ensureDirectoryExists(dirname($this->appPath.'/'.$path));
        $this->files->put($this->appPath.'/'.$path, $contents);
    }

    private function stub(string $path): string
    {
        return $this->files->get($this->appPath.'/modules/uptime/stubs/'.$path);
    }
}
