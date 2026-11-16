<?php

namespace Saucebase\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * The `dev` branch installs as `dev-dev`, which no `^3.x` constraint accepts on its
 * own. Its branch alias names the major (`3.x-dev`, as Laravel does), which sorts above
 * every 3.x release, so apps can test it against modules that require a release and it
 * never falls behind a minor release. Only a new major moves it.
 */
class BranchAliasTest extends TestCase
{
    public function test_the_dev_branch_is_aliased_to_the_newest_releases_major(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);
        $alias = $composer['extra']['branch-alias']['dev-dev'] ?? null;

        $this->assertIsString($alias, 'composer.json needs extra.branch-alias.dev-dev');
        $this->assertMatchesRegularExpression('/^\d+\.x-dev$/', $alias, 'Alias the major only, like 3.x-dev.');

        $tags = new Process(['git', 'tag', '--list', 'v*', '--sort=-v:refname'], dirname(__DIR__, 2));
        $newest = strtok((string) $tags->mustRun()->getOutput(), "\n");

        if ($newest === false) {
            $this->markTestSkipped('No release tags in this checkout.');
        }

        $this->assertGreaterThanOrEqual(
            (int) ltrim($newest, 'v'),
            (int) $alias,
            "dev-dev is aliased {$alias}, behind {$newest}: alias the new major.",
        );
    }
}
