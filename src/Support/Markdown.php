<?php

namespace Saucebase\Core\Support;

/**
 * Mail lines are rendered as Markdown, so text a user controls (a name, a workspace,
 * a User-Agent) could turn into a link or an image inside an email sent from the
 * app's own address. Escape it before it goes into a line or greeting.
 *
 * Only the characters that build links, images, emphasis and code are escaped, so
 * ordinary text like `Work laptop (1.0)` reaches the email unchanged. Subjects are
 * plain text and need none of this.
 */
class Markdown
{
    public static function escape(string $text): string
    {
        return preg_replace('/([\\\\`*_\[\]<>!])/', '\\\\$1', $text) ?? $text;
    }
}
