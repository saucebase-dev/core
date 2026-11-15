<?php

namespace Saucebase\Core\Tests\Unit;

use Composer\Semver\Comparator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * The `dev` branch installs as `dev-dev`, which no `^2.x` constraint accepts on its
 * own. Its branch alias names the next minor, so apps can test it against modules
 * that require a release. After a release, the alias moves to the minor after it.
 */
class BranchAliasTest extends TestCase
{
    public function test_the_dev_branch_is_aliased_ahead_of_the_newest_release(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);
        $alias = $composer['extra']['branch-alias']['dev-dev'] ?? null;

        $this->assertIsString($alias, 'composer.json needs extra.branch-alias.dev-dev');
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.x-dev$/', $alias);

        $tags = new Process(['git', 'tag', '--list', 'v*', '--sort=-v:refname'], dirname(__DIR__, 2));
        $newest = strtok((string) $tags->mustRun()->getOutput(), "\n");

        if ($newest === false) {
            $this->markTestSkipped('No release tags in this checkout.');
        }

        // 2.13.x-dev against v2.12.0: compare the alias's minor as 2.13.0.
        $this->assertTrue(
            Comparator::greaterThan(str_replace('.x-dev', '.0', $alias), ltrim($newest, 'v')),
            "dev-dev is aliased {$alias}, but {$newest} is already released: move the alias past it.",
        );
    }
}
