<?php

namespace Saucebase\Core\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Saucebase\Core\Tests\Fixtures\Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;

/**
 * A user for core's tests to log in as.
 *
 * Core's own code never references a user model — `SectionRegistry`, `Navigation`,
 * the middleware and the settings classes are all indifferent to who is signed in.
 * This exists because Filament's panel refuses an unauthenticated request, so
 * testing core's admin pages requires *a* user, not this user.
 *
 * The application's real `App\Models\User` stays in the application by design
 * (see sc-651). Nothing core ships may reference this class: it is autoload-dev
 * only, and it deliberately implements just the contracts the panel demands.
 */
class User extends Authenticatable implements FilamentUser, HasLocalePreference
{
    use HasFactory;
    use HasRoles;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
