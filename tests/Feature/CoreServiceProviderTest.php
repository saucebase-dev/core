<?php

namespace Saucebase\Core\Tests\Feature;

use Saucebase\Core\CoreServiceProvider;
use Saucebase\Core\Tests\TestCase;

class CoreServiceProviderTest extends TestCase
{
    public function test_the_package_provider_boots(): void
    {
        $this->assertTrue($this->app->providerIsLoaded(CoreServiceProvider::class));
    }
}
