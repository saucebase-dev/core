<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Support\Facades\Process;
use Illuminate\Testing\PendingCommand;
use Modules\ModuleTestFixture\Console\Commands\InstallFixtureCommand;
use Modules\ModuleTestFixture\Database\Seeders\DatabaseSeeder;
use Modules\ModuleTestFixture\Enums\FixtureStatus;
use Modules\ModuleTestFixture\Settings\FixtureSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use Saucebase\Core\Tests\Concerns\InteractsWithFixtureModules;
use Saucebase\Core\Tests\TestCase;

/**
 * Settings marked #[InstallSetting] are set while modules are installed (`--configure`):
 * from a template's `configure`, a `--set` flag, or a prompt. Flag beats answer
 * beats template beats the setting's own default. Anything else is refused up front.
 */
class InstallSettingsTest extends TestCase
{
    use InteractsWithFixtureModules;

    private const COMPOSER = __DIR__.'/../Fixtures/modules/module-order-fixture/composer.json';

    private string $original;

    protected function setUp(): void
    {
        parent::setUp();

        Process::fake(['*--reverse*' => Process::result(exitCode: 1), '*' => Process::result()]);
        DatabaseSeeder::$ran = [];
        $this->original = (string) file_get_contents(self::COMPOSER);
    }

    protected function tearDown(): void
    {
        file_put_contents(self::COMPOSER, $this->original);

        parent::tearDown();
    }

    /** The second fixture plays the template: it sets the first one's settings. */
    private function template(array $settings): void
    {
        $json = json_decode($this->original, true);
        $json['extra']['saucebase']['configure'] = ['module-test-fixture' => $settings];
        file_put_contents(self::COMPOSER, json_encode($json));
    }

    /** @param  array<string, mixed>  $options */
    private function install(array $options = []): PendingCommand
    {
        return $this->artisan('saucebase:module-setup', ['modules' => ['module-order-fixture', 'module-test-fixture'], '--configure' => true] + $options);
    }

    private function settings(): FixtureSettings
    {
        return app(FixtureSettings::class)->refresh();
    }

    public function test_a_templates_settings_are_applied(): void
    {
        $this->template(['signups' => true, 'days' => 7, 'status' => 'published']);

        $this->install(['--no-interaction' => true])->assertSuccessful();

        $this->assertTrue($this->settings()->signups);
        $this->assertSame(7, $this->settings()->days);
        $this->assertSame(FixtureStatus::Published, $this->settings()->status);
    }

    public function test_a_flag_wins_over_the_template(): void
    {
        $this->template(['days' => 7]);

        $this->install(['--no-interaction' => true, '--set' => ['module-test-fixture.days=9', 'module-test-fixture.signups=yes']])->assertSuccessful();

        $this->assertSame(9, $this->settings()->days);
        $this->assertTrue($this->settings()->signups);
    }

    /** A plain re-run of setup never touches settings an admin may have changed since. */
    public function test_without_configure_nothing_is_set(): void
    {
        $this->template(['days' => 7]);

        $this->artisan('saucebase:module-setup', ['modules' => ['module-order-fixture', 'module-test-fixture'], '--no-interaction' => true])->assertSuccessful();

        $this->assertSame(30, $this->settings()->days);
    }

    /** @return array<string, array{0: array<string, mixed>, 1: list<string>}> */
    public static function refused(): array
    {
        return [
            'an unmarked setting' => [['secret' => 'leaked'], []],
            'a value outside its rules' => [['days' => 500], []],
            'not a whole number' => [['days' => 'many'], []],
            'not a yes or no' => [['signups' => 'maybe'], []],
            'not one of the cases' => [['status' => 'gone'], []],
            'a flag for a module not in the run' => [[], ['billing.currency=EUR']],
            'a flag without a value' => [[], ['module-test-fixture.days']],
        ];
    }

    /**
     * @param  array<string, mixed>  $template
     * @param  list<string>  $flags
     */
    #[DataProvider('refused')]
    public function test_a_bad_setting_fails_before_anything_runs(array $template, array $flags): void
    {
        $this->template($template);

        $this->install(['--no-interaction' => true, '--set' => $flags])
            ->expectsOutputToContain('configure')
            ->assertFailed();

        $this->assertSame([], DatabaseSeeder::$ran);
    }

    public function test_the_person_installing_answers_each_setting(): void
    {
        $this->install()
            ->expectsConfirmation('Configure module-test-fixture?', 'yes')
            ->expectsConfirmation('Let people sign up?', 'yes')
            ->expectsQuestion('Days to keep', '12')
            ->expectsChoice('Status', 'published', ['draft' => 'Draft', 'published' => 'Published'])
            ->assertSuccessful();

        $this->assertTrue($this->settings()->signups);
        $this->assertSame(12, $this->settings()->days);
        $this->assertSame(FixtureStatus::Published, $this->settings()->status);
    }

    /** Declining keeps the template's values; a flag's setting is not asked. */
    public function test_declining_keeps_the_templates_values(): void
    {
        $this->template(['days' => 7]);

        $this->install()
            ->expectsConfirmation('Configure module-test-fixture?', 'no')
            ->assertSuccessful();

        $this->assertSame(7, $this->settings()->days);
    }

    public function test_a_flagged_setting_is_not_asked(): void
    {
        $this->install(['--set' => ['module-test-fixture.days=9']])
            ->expectsConfirmation('Configure module-test-fixture?', 'yes')
            ->expectsConfirmation('Let people sign up?', 'no')
            ->expectsChoice('Status', 'draft', ['draft' => 'Draft', 'published' => 'Published'])
            ->assertSuccessful();

        $this->assertSame(9, $this->settings()->days);
    }

    /** A key that is no install setting is an answer for the module's install command. */
    public function test_other_keys_answer_the_install_command(): void
    {
        InstallFixtureCommand::$options = [];

        $this->install(['--no-interaction' => true, '--set' => ['module-test-fixture.onboarding=create']])->assertSuccessful();

        $this->assertSame('create', InstallFixtureCommand::$options['onboarding']);
    }

    /** A key no install setting and no install command option takes fails before anything runs. */
    public function test_an_unknown_key_fails_before_anything_runs(): void
    {
        $this->template(['nope' => true]);

        $this->install(['--no-interaction' => true])
            ->expectsOutputToContain('configure: module-test-fixture.nope')
            ->assertFailed();

        $this->assertSame([], DatabaseSeeder::$ran);
    }

    /** A module with no install command takes no answers, so an unmatched key is refused. */
    public function test_a_module_without_an_install_command_refuses_other_keys(): void
    {
        $this->install(['--no-interaction' => true, '--set' => ['module-order-fixture.nope=true']])
            ->expectsOutputToContain('configure: module-order-fixture.nope')
            ->assertFailed();
    }

    /** An option that takes no value is a flag: true passes it, false leaves it out. */
    public function test_a_flag_option_takes_true_and_false(): void
    {
        InstallFixtureCommand::$options = [];
        $this->install(['--no-interaction' => true, '--set' => ['module-test-fixture.single=true']])->assertSuccessful();
        $this->assertTrue(InstallFixtureCommand::$options['single']);

        InstallFixtureCommand::$options = [];
        $this->template(['single' => true]);
        $this->install(['--no-interaction' => true, '--set' => ['module-test-fixture.single=false']])->assertSuccessful();
        $this->assertFalse(InstallFixtureCommand::$options['single']);
    }
}
