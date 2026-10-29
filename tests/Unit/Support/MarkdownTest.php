<?php

namespace Saucebase\Core\Tests\Unit\Support;

use Illuminate\Mail\Markdown as MailMarkdown;
use PHPUnit\Framework\Attributes\DataProvider;
use Saucebase\Core\Support\Markdown;
use Saucebase\Core\Tests\TestCase;

class MarkdownTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function payloads(): array
    {
        return [
            'link' => ['[Secure your account](https://evil.example)'],
            'image' => ['![x](https://evil.example/p.png)'],
            'reference link' => ["[Secure][1]\n\n[1]: https://evil.example"],
            'emphasis' => ['**urgent** _now_ `code`'],
        ];
    }

    #[DataProvider('payloads')]
    public function test_escaped_text_renders_as_the_same_plain_text(string $payload): void
    {
        $html = (string) MailMarkdown::parse(Markdown::escape($payload));

        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<strong>', $html);
        $this->assertStringNotContainsString('<code>', $html);
        $this->assertStringContainsString(strtok($payload, "\n"), html_entity_decode(strip_tags($html)));
    }

    public function test_ordinary_text_is_left_alone(): void
    {
        $this->assertSame('Ana Souza (Work laptop) 1.0 - #3', Markdown::escape('Ana Souza (Work laptop) 1.0 - #3'));
    }
}
