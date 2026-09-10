<?php

namespace Saucebase\Core\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Inertia\Response;

class InertiaServiceProvider extends ServiceProvider
{
    /**
     * Give a controller a per-response say over SSR.
     *
     * These macros are the other half of HandleInertiaRequests, which turns SSR off at
     * the start of every request; without something to turn it back on, `withSSR()`
     * would have nothing to flip. They lived in their own MacroServiceProvider, which
     * meant reading two files to understand one behaviour.
     *
     * Both the request attribute and the config value are written. The attribute is the
     * one that holds up under Octane, where a worker serves overlapping requests and
     * config is not per-request state; the config value is what Inertia reads when it
     * renders.
     */
    public function boot(): void
    {
        Response::macro('withSSR', function () {
            /** @var Response $this */
            request()->attributes->set('inertia.ssr', true);
            Config::set('inertia.ssr.enabled', true);

            return $this;
        });

        Response::macro('withoutSSR', function () {
            /** @var Response $this */
            request()->attributes->set('inertia.ssr', false);
            Config::set('inertia.ssr.enabled', false);

            return $this;
        });
    }
}
