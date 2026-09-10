{{--
    The Inertia root view for core's test application.

    The real one lives in the application (resources/views/app.blade.php) and carries
    branding, favicons, an appearance script and a Vite entry point — none of which
    core owns or tests. This keeps only what Inertia itself needs: a document, and the
    component that writes the page payload into it, which is what the page-payload
    assertions read back.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
