<?php

namespace Saucebase\Core\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Saucebase\Core\Http\Middleware\SecureHeaders;

class SecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureSecureUrls();
        $this->letAdminsPassEveryCheck();
    }

    /**
     * Admins pass every can()/policy check. Others get null, so their own permissions
     * decide (false would deny everyone). hasPermissionTo() skips this.
     */
    protected function letAdminsPassEveryCheck(): void
    {
        Gate::before(fn (Authenticatable $user): ?bool => method_exists($user, 'hasRole') && $user->hasRole('admin') ? true : null);
    }

    /**
     * Generate https:// URLs, and say so in the response headers, where it is warranted.
     *
     * Laravel builds URLs from the incoming request, so an app behind a TLS-terminating
     * proxy sees http:// and emits http:// links on an https:// page. Forcing the scheme
     * is the fix; the HTTPS server variable is set alongside it for code that reads the
     * request directly rather than going through the URL generator.
     *
     * Local is included when app.url is itself https, which is how Herd serves sites, so
     * that development matches production instead of only failing once deployed. Tests
     * are always excluded: forcing the scheme there breaks URL assertions for no gain.
     */
    protected function configureSecureUrls(): void
    {
        $enforceHttps = $this->app->environment(['production', 'staging'])
            && ! $this->app->runningUnitTests();

        $localHttps = $this->app->environment('local')
            && config('app.url')
            && str_starts_with((string) config('app.url'), 'https://')
            && ! $this->app->runningUnitTests();

        $useHttps = $enforceHttps || $localHttps;

        URL::forceHttps($useHttps);

        if ($useHttps) {
            $this->app['request']->server->set('HTTPS', 'on');
        }

        if ($enforceHttps) {
            $this->app['router']->pushMiddlewareToGroup('web', SecureHeaders::class);
        }
    }
}
