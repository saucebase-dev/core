<?php

namespace Saucebase\Core\Services;

use Closure;
use Illuminate\Http\Request;

/**
 * Where a signed-in user lands: the dashboard until the application says otherwise,
 * and the site itself when it says there is no such place (a resolver returning null).
 *
 * The application decides in a service provider, the way it decides with Laravel's
 * `redirectUsersTo()`; modules ask here rather than naming a route. The reason lets a
 * new account go somewhere a returning one does not, onboarding for instance.
 */
class Home
{
    public const VISIT = 'visit';

    public const LOGIN = 'login';

    public const REGISTERED = 'registered';

    public const VERIFIED = 'verified';

    /** @var (Closure(Request, string): ?string)|null */
    private ?Closure $resolver = null;

    /**
     * @param  Closure(Request, string): ?string  $resolver
     */
    public function using(Closure $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function url(Request $request, string $reason = self::VISIT): string
    {
        if (! $this->resolver) {
            return route('dashboard');
        }

        return ($this->resolver)($request, $reason) ?? route('index');
    }
}
