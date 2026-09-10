<?php

namespace Saucebase\Core\Providers;

use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->keepDatesInTheApplicationLanguage();
    }

    /**
     * Teach Carbon which language the application is currently speaking.
     *
     * Laravel does not do this itself: `App::setLocale()` moves the translator and leaves
     * Carbon behind, so a date rendered with `isoFormat()` keeps its English month names
     * in a Portuguese email. Listening for the event rather than setting it in middleware
     * covers the paths a request never touches — queued mail and notifications, which set
     * the locale themselves from the recipient's `preferredLocale()`.
     */
    private function keepDatesInTheApplicationLanguage(): void
    {
        Carbon::setLocale($this->app->getLocale());

        Event::listen(LocaleUpdated::class, function (LocaleUpdated $event): void {
            Carbon::setLocale($event->locale);
        });
    }
}
