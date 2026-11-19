<?php

namespace Saucebase\Core;

use Illuminate\Support\Str;

/**
 * One address allowed to sign up while sign-up is closed, because someone invited it.
 *
 * The module that invites (tenancy) allows it; the module that registers (auth) reads
 * it, so neither knows the other. It lives in the session: it lasts as long as the
 * visit that opened the invitation, and registering uses it up.
 */
final class RegistrationInvite
{
    private const KEY = 'saucebase.registration_invite';

    public static function allow(string $email): void
    {
        session()->put(self::KEY, Str::lower($email));
    }

    public static function email(): ?string
    {
        return session(self::KEY);
    }

    public static function allows(string $email): bool
    {
        return self::email() !== null && hash_equals(self::email(), Str::lower($email));
    }

    public static function forget(): void
    {
        session()->forget(self::KEY);
    }
}
