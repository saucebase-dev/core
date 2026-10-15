<?php

return [
    /*
     * What each language is called in the language selector, written as its speakers
     * write it ("Português (Brasil)", not "Portuguese"). Naming a language here does not
     * offer it: a language comes from its lang/ files and is switched on in the admin.
     */
    'locales' => [
        'en' => 'English',
    ],

    'ignore_files' => ['module.json'],

    /*
     * The password of every account the demo seeders create. There is no default:
     * a known password on a reachable admin account is the risk this avoids.
     */
    'demo_password' => env('DEMO_PASSWORD'),
];
