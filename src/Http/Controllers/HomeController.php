<?php

namespace Saucebase\Core\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Uri;
use Saucebase\Core\Facades\Home;

/**
 * `/home`: a stable address for "where I land", asked when it is visited. A link in
 * an email or a redirect from a module follows the application's choice at that
 * moment. The query is passed on, so `/home?checkout=success` still says so.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->to((string) Uri::of(Home::url($request))->withQuery($request->query()));
    }
}
