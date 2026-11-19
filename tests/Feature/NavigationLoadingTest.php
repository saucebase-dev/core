<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Http\Request;
use Saucebase\Core\Facades\Navigation as NavigationFacade;
use Saucebase\Core\Navigation\Navigation;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * Navigation is scoped: every request (or Octane/queue job) builds a fresh instance
 * that loads the navigation files on first use. Asserted through the fixture module.
 */
class NavigationLoadingTest extends TestCase
{
    use InteractsWithFixtureModules;

    public function test_module_navigation_is_loaded_on_first_use(): void
    {
        $this->assertSame('Module Test Fixture', $this->freshNavigation()->treeGrouped()['main'][0]['title']);
    }

    public function test_every_fresh_instance_loads_module_navigation(): void
    {
        $this->freshNavigation()->treeGrouped();

        $this->assertSame('Module Test Fixture', $this->freshNavigation()->treeGrouped()['main'][0]['title']);
    }

    public function test_an_explicit_load_is_not_repeated_on_first_use(): void
    {
        $navigation = $this->freshNavigation();

        $this->assertCount(1, $navigation->load()->tree());
    }

    public function test_navigation_files_load_once_per_instance(): void
    {
        $navigation = $this->freshNavigation();
        $navigation->tree();

        $this->assertCount(1, $navigation->tree());
    }

    /** As with `require_once` before: a second explicit load is a no-op. */
    public function test_loading_twice_lists_each_item_once(): void
    {
        $this->assertCount(1, $this->freshNavigation()->load()->load()->tree());
    }

    /** Without cached routes, modular already ran the module's file into this instance at boot. */
    public function test_the_instance_filled_at_boot_lists_each_item_once(): void
    {
        $this->assertCount(1, app(Navigation::class)->tree());
    }

    public function test_breadcrumbs_load_navigation_on_first_use(): void
    {
        $path = base_path('routes/navigation.php');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, <<<'PHP'
        <?php

        Saucebase\Core\Facades\Navigation::add('Reports', 'http://localhost/reports');
        PHP);

        try {
            app()->instance('request', Request::create('http://localhost/reports'));

            $this->assertSame('Reports', $this->freshNavigation()->breadcrumbs()[0]['title']);
        } finally {
            unlink($path);
        }
    }

    /** Simulates a new request: the scoped instance is rebuilt. */
    private function freshNavigation(): Navigation
    {
        $this->app->forgetScopedInstances();
        NavigationFacade::clearResolvedInstances();

        return app(Navigation::class);
    }
}
