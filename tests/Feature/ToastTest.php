<?php

namespace Saucebase\Core\Tests\Feature;

use Saucebase\Core\Tests\TestCase;
use Saucebase\Core\Toast;

class ToastTest extends TestCase
{
    public function test_it_flashes_only_what_was_given(): void
    {
        Toast::success('Saved');

        $this->assertSame(['message' => 'Saved', 'type' => 'success'], session('toast'));
    }

    public function test_it_carries_every_option(): void
    {
        Toast::error('Failed', 'Try again', ['label' => 'Retry', 'type' => 'route', 'onClick' => 'dashboard'], 5000, Toast::POSITION_TOP_CENTER);

        $this->assertSame([
            'message' => 'Failed',
            'type' => 'error',
            'description' => 'Try again',
            'action' => ['label' => 'Retry', 'type' => 'route', 'onClick' => 'dashboard'],
            'duration' => 5000,
            'position' => 'top-center',
        ], session('toast'));
    }

    public function test_an_unknown_type_or_position_falls_back_to_the_default(): void
    {
        Toast::show('Hello', 'sparkly', position: 'middle');

        $this->assertSame(['message' => 'Hello', 'type' => 'default', 'position' => 'bottom-right'], session('toast'));
    }
}
