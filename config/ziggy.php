<?php

/*
| Routes Ziggy shares with the frontend. Admin and package internals are left out:
| no page calls them, and listing them adds weight and advertises admin URLs.
| Not a security boundary: middleware protects routes. An app's own
| config/ziggy.php replaces these keys.
*/

return [
    'except' => [
        'filament.*',
        'livewire.*',
        'default-livewire.*',
        'filament-impersonate.*',
        'boost.*',
        'storage.*',
    ],
];
