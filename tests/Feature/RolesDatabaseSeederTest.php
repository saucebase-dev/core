<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Saucebase\Core\Database\Seeders\RolesDatabaseSeeder;
use Saucebase\Core\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_platform_roles_and_permissions(): void
    {
        $this->seed(RolesDatabaseSeeder::class);
        $this->seed(RolesDatabaseSeeder::class);

        $this->assertEqualsCanonicalizing(['admin', 'user'], Role::pluck('name')->all());
        $this->assertEqualsCanonicalizing(['access admin panel', 'manage settings'], Permission::pluck('name')->all());
    }
}
