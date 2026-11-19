<?php

namespace Saucebase\Core\Tests\Feature;

use Saucebase\Core\RegistrationInvite;
use Saucebase\Core\Tests\TestCase;

class RegistrationInviteTest extends TestCase
{
    public function test_nobody_is_invited_until_a_module_says_so(): void
    {
        $this->assertNull(RegistrationInvite::email());
        $this->assertFalse(RegistrationInvite::allows('ana@example.com'));
    }

    /** Only the invited address, whatever its case; nothing else. */
    public function test_an_invite_allows_its_own_address_only(): void
    {
        RegistrationInvite::allow('Ana@Example.com');

        $this->assertSame('ana@example.com', RegistrationInvite::email());
        $this->assertTrue(RegistrationInvite::allows('ANA@example.com'));
        $this->assertFalse(RegistrationInvite::allows('bob@example.com'));
    }

    public function test_a_used_invite_is_forgotten(): void
    {
        RegistrationInvite::allow('ana@example.com');

        RegistrationInvite::forget();

        $this->assertNull(RegistrationInvite::email());
    }
}
