<?php

namespace Saucebase\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Saucebase\Core\Settings\LocalizationSettings;

class LocalizationController
{
    /**
     * Switch the session to another language.
     *
     * The trust boundary for the setting: the selector only offers enabled languages, but
     * nothing stops a client from posting any code, so the check happens here.
     *
     * `forceFill` rather than assignment because core does not own the user model: it
     * adds `locale` by migration, so the column exists but is declared nowhere core can
     * see. Writing it as an array keeps `locale` off `$fillable` — it is set from a
     * validated route segment, never from request input — without naming a property that
     * static analysis has no way to resolve.
     */
    public function __invoke(Request $request, string $locale): JsonResponse
    {
        $enabledLocales = array_keys(app(LocalizationSettings::class)->enabled());

        if (! in_array($locale, $enabledLocales, true)) {
            return new JsonResponse(['error' => 'Invalid locale'], 400);
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return new JsonResponse(['locale' => App::getLocale()]);
    }
}
