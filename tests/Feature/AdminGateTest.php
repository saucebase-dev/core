<?php

namespace Saucebase\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Saucebase\Core\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_passes_every_check(): void
    {
        Permission::findOrCreate('manage blog');
        Role::findOrCreate('admin');

        $admin = $this->createUser()->assignRole('admin');

        $this->assertTrue($admin->can('manage blog'));
    }

    public function test_other_users_are_decided_by_their_own_permissions(): void
    {
        Permission::findOrCreate('manage blog');
        Permission::findOrCreate('manage themes');

        $user = $this->createUser()->givePermissionTo('manage blog');

        $this->assertTrue($user->can('manage blog'));
        $this->assertFalse($user->can('manage themes'));
    }
}
