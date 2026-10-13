<?php

namespace Saucebase\Core\Console\Traits;

use Symfony\Component\Console\Terminal;

/**
 * The Saucebase banner an install command opens with.
 *
 * The `saucebase` CLI keeps a copy of the artwork: it runs before this package is installed.
 */
trait DisplaysBanner
{
    /**
     * Shown to a person only: an unattended run (`--no-interaction`) keeps its output
     * clean, which is also how a command calling this one avoids a second banner.
     */
    protected function displayBanner(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $primary = '#5455c4';
        $secondary = '#26b9d9';
        $width = 84;

        $this->newLine();

        // The artwork does not wrap: a terminal narrower than it gets the name.
        if ((new Terminal)->getWidth() < $width) {
            $this->line("<fg={$secondary};options=bold>Sauce</><fg={$primary};options=bold>base</>");
            $this->newLine();

            return;
        }

        $lines = [
            '                                                888                                 ',
            '                                                888                                 ',
            '                                                888                                 ',
            '    .d8888b   8888b.  888  888  .d8888b .d88b.  88888b.   8888b.  .d8888b   .d88b.  ',
            '    88K          "88b 888  888 d88P"   d8P  Y8b 888 "88b     "88b 88K      d8P  Y8b ',
            '    "Y8888b. .d888888 888  888 888     88888888 888  888 .d888888 "Y8888b. 88888888 ',
            '         X88 888  888 Y88b 888 Y88b.   Y8b.     888 d88P 888  888      X88 Y8b.     ',
            '     88888P\' "Y888888  "Y88888  "Y8888P "Y8888  88888P"  "Y888888  88888P\'  "Y8888  ',
        ];

        foreach ($lines as $line) {
            // "sauce" and "base" each take one brand colour; the split falls between them.
            $this->line("<fg={$secondary}>".substr($line, 0, 48)."</><fg={$primary}>".substr($line, 48).'</>');
        }

        $padding = "<fg=white;bg={$primary}>".str_repeat(' ', $width).'</>';

        $this->newLine(2);
        $this->line($padding);
        $this->line("<fg=white;bg={$primary};options=bold>".mb_str_pad('With Saucebase • Your foundation is ready!', $width, ' ', STR_PAD_BOTH).'</>');
        $this->line($padding);
        $this->newLine(2);
    }
}
