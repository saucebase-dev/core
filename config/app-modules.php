<?php

return [
    /*
     * Saucebase installs modules in `modules/`, not internachi/modular's default
     * `app-modules/`.
     *
     * Applied by ModuleSupportServiceProvider, which registers modular itself rather
     * than letting package discovery do it — modular reads this key during its own
     * register() and memoises the resulting path, so nothing that runs later can
     * change it. See `extra.laravel.dont-discover` in composer.json.
     */
    'modules_directory' => 'modules',
];
