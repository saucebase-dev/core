<?php

namespace Saucebase\Core\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;

/**
 * Modules add interfaces to the app's User with patches, and patches only combine when
 * none of them edits a line another one needs: two edits of one `implements` line
 * conflict in every order. One interface per line lets each module insert its own.
 */
class UserStubTest extends TestCase
{
    public function test_the_user_lists_one_interface_per_line(): void
    {
        $stub = (string) file_get_contents(__DIR__.'/../../../stubs/app/app/Models/User.php');

        $this->assertStringContainsString(
            "class User extends Authenticatable implements\n    FilamentUser,\n    HasLocalePreference,\n    HasMedia\n",
            $stub,
        );
    }
}
