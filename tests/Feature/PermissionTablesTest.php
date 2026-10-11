<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Saucebase\Core\Tests\TestCase;

class PermissionTablesTest extends TestCase
{
    use RefreshDatabase;

    /** Roles and permissions a module ships are tagged with it, so they can be grouped and removed. */
    public function test_roles_and_permissions_can_be_tagged_with_their_module(): void
    {
        $this->assertTrue(Schema::hasColumn('roles', 'module'));
        $this->assertTrue(Schema::hasColumn('permissions', 'module'));
    }
}
