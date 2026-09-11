<?php

use Saucebase\Breadcrumbs\Breadcrumbs;
use Saucebase\Breadcrumbs\Generator;

Breadcrumbs::for('module-test-fixture.index', function (Generator $trail): void {
    $trail->push('module-test-fixture.index', route('dashboard'));
});
