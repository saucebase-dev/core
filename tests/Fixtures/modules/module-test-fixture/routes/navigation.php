<?php

use Saucebase\Core\Facades\Navigation;
use Saucebase\Core\Navigation\Section;

Navigation::add('Module Test Fixture', fn (): string => route('dashboard'), function (Section $section): void {
    $section->attributes([
        'group' => 'main',
        'slug' => 'module-test-fixture',
        'icon' => 'module-test-fixture',
    ]);
});
