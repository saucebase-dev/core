<?php

namespace Saucebase\Core\Tests;

use Saucebase\Core\CoreServiceProvider;

class CoreServiceProviderTest extends TestCase
{
    public function test_the_package_provider_boots(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(CoreServiceProvider::class));
    }
}
