<?php

namespace Saucebase\Core\Tests\Frontend;

use Saucebase\Core\Frontend\FrontendConfig;
use Saucebase\Core\Tests\TestCase;

/**
 * Which front-end stack this installation has chosen, read off disk.
 *
 * `frontend.json` is written when a stack is selected, so every question this answers
 * has to survive the file not being there yet — that is the state a fresh checkout is
 * in, and the one that decides whether a visitor sees the application or the setup
 * page. Both reads are error-suppressed, which makes a malformed file indistinguishable
 * from a missing one at the call site; these pin that down as deliberate.
 */
class FrontendConfigTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = base_path('frontend.json');

        $this->writeConfig(null);
    }

    protected function tearDown(): void
    {
        $this->writeConfig(null);

        parent::tearDown();
    }

    private function writeConfig(?string $contents): void
    {
        $contents === null
            ? @unlink($this->path)
            : file_put_contents($this->path, $contents);
    }

    public function test_no_stack_has_been_chosen_when_the_file_is_missing(): void
    {
        $this->assertFileDoesNotExist($this->path);

        $config = new FrontendConfig;

        $this->assertNull($config->getFramework());
        $this->assertFalse($config->isDev());
    }

    public function test_a_malformed_file_is_treated_as_no_choice_rather_than_crashing(): void
    {
        $this->writeConfig('{ not json');

        $config = new FrontendConfig;

        $this->assertNull($config->getFramework());
        $this->assertFalse($config->isDev());
    }

    public function test_a_chosen_stack_is_reported(): void
    {
        $this->writeConfig(json_encode(['framework' => 'vue', 'dev' => true]));

        $config = new FrontendConfig;

        $this->assertSame('vue', $config->getFramework());
        $this->assertTrue($config->isDev());
    }

    /**
     * `dev` is compared identically, so anything short of true is false — a string
     * "true" out of a shell script does not quietly enable dev mode.
     */
    public function test_dev_is_only_true_for_the_boolean(): void
    {
        $this->writeConfig(json_encode(['framework' => 'react', 'dev' => 'true']));

        $this->assertFalse((new FrontendConfig)->isDev());
    }

    /**
     * The file is read once per instance: it cannot change within a request, and the
     * value is asked for on every page render.
     */
    public function test_the_file_is_read_once_per_instance(): void
    {
        $this->writeConfig(json_encode(['framework' => 'vue']));

        $config = new FrontendConfig;
        $this->assertSame('vue', $config->getFramework());

        $this->writeConfig(json_encode(['framework' => 'react']));

        $this->assertSame('vue', $config->getFramework());
        $this->assertSame('react', (new FrontendConfig)->getFramework());
    }
}
