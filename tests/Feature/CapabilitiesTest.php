<?php

namespace Saucebase\Core\Tests\Feature;

use LogicException;
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

    /**
     * Two sources claiming one key with different meanings would let the second silently
     * win: say which key.
     */
    public function test_a_key_declared_twice_in_a_group_throws(): void
    {
        CapabilitiesFacade::add('billing', ['webhooks' => []]);
        CapabilitiesFacade::add('billing', fn (): array => ['webhooks' => ['label' => 'Again']]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('[webhooks]');

        CapabilitiesFacade::group('billing');
    }

    /**
     * Modular also requires a module's `routes/*.php` at boot, so without a route cache its
     * capabilities file runs twice. One declaration running again replaces itself.
     */
    public function test_the_same_declaration_running_again_is_not_a_conflict(): void
    {
        $declare = fn () => CapabilitiesFacade::add('billing', ['webhooks' => ['label' => 'Webhooks']]);
        $declare();
        $declare();

        $this->assertSame(['webhooks' => ['label' => 'Webhooks', 'description' => '']], CapabilitiesFacade::group('billing'));
    }

    /** Two sources are a conflict even when they agree: one of them should not be declaring it. */
    public function test_two_sources_declaring_an_identical_key_throw(): void
    {
        CapabilitiesFacade::add('billing', ['webhooks' => ['label' => 'Webhooks']]);
        CapabilitiesFacade::add('billing', ['webhooks' => ['label' => 'Webhooks']]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('[webhooks]');

        CapabilitiesFacade::group('billing');
    }

    /** Groups are read by different readers, so one key may mean something in each. */
    public function test_the_same_key_may_appear_in_different_groups(): void
    {
        CapabilitiesFacade::add('billing', ['posts' => []]);

        $this->assertArrayHasKey('posts', CapabilitiesFacade::group('api'));
        $this->assertArrayHasKey('posts', CapabilitiesFacade::group('billing'));
    }
}
