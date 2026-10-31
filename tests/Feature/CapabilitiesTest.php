<?php

namespace Saucebase\Core\Tests\Feature;

use Saucebase\Core\Capabilities;
use Saucebase\Core\Facades\Capabilities as CapabilitiesFacade;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * One catalog of what can be granted, grouped by who reads it: `api` for token
 * scopes, `billing` for what a product includes. Each module declares its own in
 * `routes/capabilities.php`, like navigation.
 */
class CapabilitiesTest extends TestCase
{
    use InteractsWithFixtureModules;

    public function test_a_group_lists_what_modules_declare_in_their_capabilities_file(): void
    {
        $capabilities = $this->app->make(Capabilities::class);

        $this->assertSame(
            ['posts' => ['label' => 'Posts', 'description' => 'Read posts', 'read_only' => true]],
            $capabilities->group('api'),
        );
        $this->assertSame([], $capabilities->group('unknown'));
    }

    /** Entries from data (a row per package) are a callback, run only when the group is read. */
    public function test_entries_can_be_added_lazily(): void
    {
        $called = false;

        CapabilitiesFacade::add('billing', function () use (&$called): array {
            $called = true;

            return ['packages:acme/invoices' => ['label' => 'acme/invoices']];
        });

        $this->assertFalse($called);
        $this->assertSame(
            ['packages:acme/invoices' => ['label' => 'acme/invoices', 'description' => '']],
            CapabilitiesFacade::group('billing'),
        );
        $this->assertTrue($called);
    }

    public function test_an_entry_without_a_label_is_labelled_by_its_key(): void
    {
        CapabilitiesFacade::add('billing', ['webhooks' => []]);

        $this->assertSame(['webhooks' => ['label' => 'webhooks', 'description' => '']], CapabilitiesFacade::group('billing'));
    }

    /** Each application loads the files afresh: nothing carries over from the last one. */
    public function test_files_load_once_per_application(): void
    {
        $capabilities = $this->app->make(Capabilities::class);
        $capabilities->group('api');

        $this->assertCount(1, $capabilities->group('api'));
        $this->assertSame($capabilities, $this->app->make(Capabilities::class));
    }
}
