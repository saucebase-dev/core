<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Saucebase\Core\Facades\Home;
use Saucebase\Core\Settings\SettingsSection;
use Saucebase\Core\Tests\TestCase;

/**
 * Where a signed-in user lands is the application's call: the dashboard by default,
 * anything once it says so. `/home` asks at the moment it is visited.
 */
class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_the_dashboard_until_the_application_says_otherwise(): void
    {
        $this->assertSame(route('dashboard'), Home::url(Request::create('/')));

        $this->get('/home')->assertRedirect(route('dashboard'));
    }

    public function test_the_application_chooses_home_and_hears_why_it_is_asked(): void
    {
        Home::using(fn (Request $request, string $reason): string => url("/chat?reason={$reason}"));

        $this->assertSame(url('/chat?reason=registered'), Home::url(Request::create('/'), Home::REGISTERED));
        $this->get('/home')->assertRedirect(url('/chat?reason=visit'));
    }

    /** An app with no signed-in home sends people to the site itself. */
    public function test_no_home_is_the_site_root(): void
    {
        Home::using(fn (): ?string => null);

        $this->assertSame(route('index'), Home::url(Request::create('/')));
        $this->get('/home')->assertRedirect(route('index'));
    }

    public function test_home_keeps_the_query_it_was_given(): void
    {
        Home::using(fn (): string => url('/chat'));

        $this->get('/home?checkout=success')->assertRedirect(url('/chat?checkout=success'));
    }

    public function test_a_settings_section_opens_over_home(): void
    {
        $this->assertSame(route('home').'#settings/billing', SettingsSection::url('billing'));
    }
}
