<?php

namespace Saucebase\Core;

use RuntimeException;

/**
 * Lists a module's Taskfile in the app's own, between the `BEGIN/END MODULES` markers,
 * so `task <module>:…` works. The text is edited in place: the app's comments and
 * formatting stay, and an app that removed the markers is left alone.
 */
class ModuleTaskfile
{
    public static function add(string $module): void
    {
        $taskfile = base_path('Taskfile.yml');

        if (! file_exists($taskfile)) {
            return;
        }

        $content = file_get_contents($taskfile);

        if ($content === false) {
            throw new RuntimeException("Unable to read Taskfile at [{$taskfile}].");
        }

        $entry = "    {$module}:\n        taskfile: ./modules/{$module}/Taskfile.yml\n        optional: true\n";
        $marker = '    # ── END MODULES';

        if (str_contains($content, "taskfile: ./modules/{$module}/Taskfile.yml")) {
            return;
        }

        $markerPosition = strpos($content, $marker);

        if ($markerPosition === false) {
            return;
        }

        file_put_contents($taskfile, substr($content, 0, $markerPosition).$entry.substr($content, $markerPosition));
    }
}
