<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Index')->withSSR();
    }

    public function terms(): Response
    {
        return Inertia::render('Terms')->withSSR();
    }

    public function privacy(): Response
    {
        return Inertia::render('Privacy')->withSSR();
    }
}
