<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/** `saucebase:taskfile-sync` makes the app's Taskfile list the modules installed here. */
class SyncModuleTaskfileCommandTest extends TestCase
{
    use InteractsWithFixtureModules;

    public function test_it_adds_installed_modules_and_removes_the_rest(): void
    {
        $taskfile = $this->taskfile(
            "includes:\n    # ── BEGIN MODULES\n    gone:\n        taskfile: ./modules/gone/Taskfile.yml\n        optional: true\n    # ── END MODULES\n\ntasks:\n    # kept\n"
        );

        $this->artisan('saucebase:taskfile-sync')->assertSuccessful();

        $content = file_get_contents($taskfile);
        $this->assertStringContainsString("    module-test-fixture:\n        taskfile: ./modules/module-test-fixture/Taskfile.yml\n        optional: true\n    # ── END MODULES", $content);
        $this->assertStringNotContainsString('gone', $content);
        $this->assertStringNotContainsString('module-order-fixture', $content);
        $this->assertStringContainsString("tasks:\n    # kept\n", $content);
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $taskfile = $this->taskfile("includes:\n    # ── BEGIN MODULES\n    # ── END MODULES\n");

        $this->artisan('saucebase:taskfile-sync')->assertSuccessful();
        $first = file_get_contents($taskfile);
        $this->artisan('saucebase:taskfile-sync')->assertSuccessful();

        $this->assertSame($first, file_get_contents($taskfile));
    }

    public function test_a_taskfile_without_the_markers_is_left_alone(): void
    {
        $taskfile = $this->taskfile($content = "tasks:\n    dev:\n        cmd: npm run dev\n");

        $this->artisan('saucebase:taskfile-sync')->assertFailed();

        $this->assertSame($content, file_get_contents($taskfile));
    }

    private function taskfile(string $content): string
    {
        $appPath = sys_get_temp_dir().'/saucebase-taskfile-sync-'.uniqid();
        mkdir($appPath);
        file_put_contents($appPath.'/Taskfile.yml', $content);
        $this->app->setBasePath($appPath);
        $this->beforeApplicationDestroyed(fn () => (new Filesystem)->deleteDirectory($appPath));

        return $appPath.'/Taskfile.yml';
    }
}
