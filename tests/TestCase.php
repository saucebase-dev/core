<?php

namespace Saucebase\Core\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Saucebase\Core\CoreServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [CoreServiceProvider::class];
    }
}
