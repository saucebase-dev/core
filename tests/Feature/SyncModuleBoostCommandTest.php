<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use Saucebase\Core\Tests\TestCase;

/**
 * `modules:boost` makes installed modules visible to Laravel Boost.
 *
 * Boost reads a package's `resources/boost` from `<vendor-dir>/<package>`, but modules
 * install into `modules/<name>`. The command bridges each one with a symlink and lists
 * it in boost.json's `packages`, recording what it manages so a later run can undo it.
 *
 * Every test runs against a throwaway application directory, never the real one.
 */
class SyncModuleBoostCommandTest extends TestCase
{
    private string $root;

    private string $originalBasePath;

    /** @var list<string> */
    private array $installed = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/sync-module-boost-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/modules', 0777, true);
        file_put_contents($this->root.'/composer.json', '{}');

        $this->originalBasePath = $this->app->basePath();
        $this->app->setBasePath($this->root);

        $this->app->instance(ModuleRegistry::class, new ModuleRegistry(
            $this->root.'/modules',
            fn (): Collection => collect($this->installed)->mapWithKeys(fn (string $name): array => [
                $name => new ModuleConfig($name, $this->root.'/modules/'.$name),
            ]),
        ));
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalBasePath);
        (new Filesystem)->deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_it_bridges_and_registers_a_module_with_boost_resources(): void
    {
        $this->module('auth');
        $this->boostJson(['agents' => ['claude_code']]);

        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertSame('../../modules/auth', readlink($this->root.'/vendor/saucebase/auth'));
        $this->assertSame(['saucebase/auth'], $this->readBoostJson()['packages']);
        $this->assertSame(
            ['saucebase/auth' => ['link' => 'vendor/saucebase/auth', 'target' => '../../modules/auth']],
            $this->readBoostJson()['saucebase']['managed'],
        );
    }

    public function test_core_is_registered_when_it_ships_boost_resources(): void
    {
        mkdir($this->root.'/vendor/saucebase/core/resources/boost', 0777, true);
        $this->boostJson([]);

        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertSame(['saucebase/core'], $this->readBoostJson()['packages']);
        $this->assertArrayNotHasKey('saucebase', $this->readBoostJson());
    }

    public function test_a_module_without_boost_resources_is_ignored(): void
    {
        $this->module('blog', withBoost: false);
        $this->boostJson([]);

        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertFileDoesNotExist($this->root.'/vendor/saucebase/blog');
        $this->assertArrayNotHasKey('packages', $this->readBoostJson());
    }

    public function test_an_existing_link_to_the_module_is_adopted(): void
    {
        $this->module('tenancy');
        $this->link('../../modules/tenancy/', 'vendor/saucebase/tenancy');
        $this->boostJson([]);

        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertSame(['saucebase/tenancy'], $this->readBoostJson()['packages']);
        $this->assertSame(
            ['link' => 'vendor/saucebase/tenancy', 'target' => '../../modules/tenancy/'],
            $this->readBoostJson()['saucebase']['managed']['saucebase/tenancy'],
        );
    }

    public function test_a_removed_module_is_unregistered_and_its_link_deleted(): void
    {
        $this->module('auth');
        $this->boostJson([]);
        $this->artisan('modules:boost')->assertSuccessful();

        $this->installed = [];
        $this->app->make(ModuleRegistry::class)->reload();
        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertFalse(is_link($this->root.'/vendor/saucebase/auth'));
        $this->assertArrayNotHasKey('packages', $this->readBoostJson());
        $this->assertArrayNotHasKey('saucebase', $this->readBoostJson());
    }

    public function test_a_managed_link_someone_replaced_is_left_alone(): void
    {
        $this->module('auth');
        $this->boostJson([]);
        $this->artisan('modules:boost')->assertSuccessful();

        unlink($this->root.'/vendor/saucebase/auth');
        mkdir($this->root.'/elsewhere');
        $this->link('../../elsewhere', 'vendor/saucebase/auth');
        $this->installed = [];
        $this->app->make(ModuleRegistry::class)->reload();

        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertSame('../../elsewhere', readlink($this->root.'/vendor/saucebase/auth'));
        $this->assertArrayNotHasKey('saucebase', $this->readBoostJson());
    }

    public function test_a_real_directory_at_the_bridge_path_is_a_collision(): void
    {
        $this->module('auth');
        mkdir($this->root.'/vendor/saucebase/auth', 0777, true);
        file_put_contents($this->root.'/vendor/saucebase/auth/keep.txt', 'mine');
        $this->boostJson(['packages' => ['saucebase/auth']]);

        $this->artisan('modules:boost')
            ->expectsOutputToContain('saucebase/auth')
            ->assertSuccessful();

        $this->assertFalse(is_link($this->root.'/vendor/saucebase/auth'));
        $this->assertFileExists($this->root.'/vendor/saucebase/auth/keep.txt');
        $this->assertArrayNotHasKey('packages', $this->readBoostJson());
    }

    public function test_a_link_to_another_target_is_a_collision(): void
    {
        $this->module('auth');
        mkdir($this->root.'/elsewhere');
        $this->link('../../elsewhere', 'vendor/saucebase/auth');
        $this->boostJson([]);

        $this->artisan('modules:boost')
            ->expectsOutputToContain('saucebase/auth')
            ->assertSuccessful();

        $this->assertSame('../../elsewhere', readlink($this->root.'/vendor/saucebase/auth'));
        $this->assertArrayNotHasKey('packages', $this->readBoostJson());
    }

    public function test_unmanaged_packages_and_other_keys_are_preserved(): void
    {
        $this->module('auth');
        $this->boostJson([
            'agents' => ['claude_code', 'codex'],
            'guidelines' => true,
            'packages' => ['saucebase/core', 'vendor/other'],
            'skills' => ['laravel-best-practices'],
        ]);

        $this->artisan('modules:boost')->assertSuccessful();

        $config = $this->readBoostJson();
        $this->assertSame(['saucebase/auth', 'saucebase/core', 'vendor/other'], $config['packages']);
        $this->assertSame(['claude_code', 'codex'], $config['agents']);
        $this->assertTrue($config['guidelines']);
        $this->assertSame(['laravel-best-practices'], $config['skills']);
    }

    public function test_malformed_json_fails_without_changing_anything(): void
    {
        $this->module('auth');
        file_put_contents($this->root.'/boost.json', '{"packages": [');

        $this->artisan('modules:boost')->assertFailed();

        $this->assertSame('{"packages": [', file_get_contents($this->root.'/boost.json'));
        $this->assertDirectoryDoesNotExist($this->root.'/vendor');
    }

    public function test_packages_of_the_wrong_type_fails_without_changing_anything(): void
    {
        $this->module('auth');
        $this->boostJson(['packages' => 'saucebase/auth']);
        $before = file_get_contents($this->root.'/boost.json');

        $this->artisan('modules:boost')->assertFailed();

        $this->assertSame($before, file_get_contents($this->root.'/boost.json'));
        $this->assertDirectoryDoesNotExist($this->root.'/vendor');
    }

    public function test_a_managed_map_of_the_wrong_shape_fails_without_changing_anything(): void
    {
        $this->module('auth');
        $this->boostJson(['saucebase' => ['managed' => ['saucebase/auth']]]);
        $before = file_get_contents($this->root.'/boost.json');

        $this->artisan('modules:boost')->assertFailed();

        $this->assertSame($before, file_get_contents($this->root.'/boost.json'));
        $this->assertDirectoryDoesNotExist($this->root.'/vendor');
    }

    public function test_without_boost_json_nothing_is_written(): void
    {
        $this->module('auth');

        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertFileDoesNotExist($this->root.'/boost.json');
        $this->assertDirectoryDoesNotExist($this->root.'/vendor');
    }

    public function test_it_honours_a_custom_vendor_dir(): void
    {
        $this->module('auth');
        file_put_contents($this->root.'/composer.json', '{"config": {"vendor-dir": "lib/deps"}}');
        $this->boostJson([]);

        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertSame('../../../modules/auth', readlink($this->root.'/lib/deps/saucebase/auth'));
        $this->assertSame('lib/deps/saucebase/auth', $this->readBoostJson()['saucebase']['managed']['saucebase/auth']['link']);
    }

    public function test_changing_the_vendor_dir_moves_the_bridge(): void
    {
        $this->module('auth');
        $this->boostJson([]);
        $this->artisan('modules:boost')->assertSuccessful();

        file_put_contents($this->root.'/composer.json', '{"config": {"vendor-dir": "lib/deps"}}');
        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertFalse(is_link($this->root.'/vendor/saucebase/auth'));
        $this->assertSame('../../../modules/auth', readlink($this->root.'/lib/deps/saucebase/auth'));
        $this->assertSame(['saucebase/auth'], $this->readBoostJson()['packages']);
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $this->module('auth');
        $this->boostJson(['agents' => ['claude_code']]);
        $this->artisan('modules:boost')->assertSuccessful();

        $bytes = file_get_contents($this->root.'/boost.json');
        $inode = lstat($this->root.'/vendor/saucebase/auth')['ino'];

        $this->artisan('modules:boost')->assertSuccessful();

        $this->assertSame($bytes, file_get_contents($this->root.'/boost.json'));
        $this->assertSame($inode, lstat($this->root.'/vendor/saucebase/auth')['ino']);
    }

    private function module(string $name, bool $withBoost = true): void
    {
        $path = $this->root.'/modules/'.$name;
        mkdir($path, 0777, true);
        file_put_contents($path.'/composer.json', json_encode(['name' => 'saucebase/'.$name]));

        if ($withBoost) {
            mkdir($path.'/resources/boost/guidelines', 0777, true);
            file_put_contents($path.'/resources/boost/guidelines/core.md', "## {$name}\n");
        }

        $this->installed[] = $name;
    }

    private function link(string $target, string $link): void
    {
        @mkdir(dirname($this->root.'/'.$link), 0777, true);
        symlink($target, $this->root.'/'.$link);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function boostJson(array $config): void
    {
        file_put_contents($this->root.'/boost.json', json_encode((object) $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    /**
     * @return array<string, mixed>
     */
    private function readBoostJson(): array
    {
        return json_decode((string) file_get_contents($this->root.'/boost.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
