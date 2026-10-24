<?php

namespace ___MODULE_NAMESPACE___\___Module___\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_page_renders(): void
    {
        // `false`: module pages live outside the paths Inertia checks for page files.
        $this->get('/{module-}')->assertOk()->assertInertia(fn ($page) => $page
            ->component('{Module}::Index', false)
            ->where('module', '{module}')
        );
    }
}
