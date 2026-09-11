<?php

namespace Saucebase\Core\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Inertia\Response;
use Saucebase\Core\Settings\GeneralSettings;

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
        $this->shareBrandWithRootView();

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

    /**
     * Put the brand on the Inertia root view as `$brand`.
     *
     * Replaces an `@inject` that named `Saucebase\Core\Settings\GeneralSettings` in
     * three separate blade files — the application's root view and both stack stubs —
     * hardcoding a core class path into templates the application owns.
     *
     * A composer rather than `View::share()` because it is lazy: it fires only when the
     * root view actually renders, so a JSON response or a Filament page never resolves
     * the settings at all.
     */
    private function shareBrandWithRootView(): void
    {
        View::composer('app', function (ViewInstance $view): void {
            $view->with('brand', $this->app->make(GeneralSettings::class));
        });
    }
}
