<?php

use Saucebase\Core\Facades\Capabilities;

Capabilities::add('api', [
    'posts' => ['label' => 'Posts', 'description' => 'Read posts', 'read_only' => true],
]);
