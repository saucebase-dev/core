<?php

namespace Saucebase\Core\Tests\Unit\Casts;

use Illuminate\Database\Eloquent\Model;
use Saucebase\Core\Casts\SanitizedHtml;
use Saucebase\Core\Tests\TestCase;

/**
 * Rich-editor HTML a page renders as markup (`v-html`): cleaned on every read, stored as
 * written, so nothing an editor saved is lost and nothing that runs reaches a visitor.
 */
class SanitizedHtmlTest extends TestCase
{
    private function model(?string $html): Model
    {
        $model = new class extends Model
        {
            protected $casts = ['body' => SanitizedHtml::class];
        };
        $model->setRawAttributes(['body' => $html]);

        return $model;
    }

    public function test_markup_that_runs_is_removed_and_formatting_kept(): void
    {
        $body = $this->model('<p><strong>Sale</strong><img src=x onerror="alert(1)"><script>alert(2)</script></p>')->getAttribute('body');

        $this->assertStringContainsString('<strong>Sale</strong>', $body);
        $this->assertStringNotContainsString('onerror', $body);
        $this->assertStringNotContainsString('<script', $body);
    }

    public function test_null_stays_null(): void
    {
        $this->assertNull($this->model(null)->getAttribute('body'));
    }

    public function test_the_stored_value_is_what_was_written(): void
    {
        $model = $this->model(null);
        $model->setAttribute('body', '<p onclick="x()">Hi</p>');

        $this->assertSame('<p onclick="x()">Hi</p>', $model->getAttributes()['body']);
    }
}
