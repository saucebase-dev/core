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

        $entry = self::entry($module);
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

    /**
     * Replaces everything between the markers with these modules.
     *
     * @param  string[]  $modules
     * @return bool False when the app has no Taskfile or no markers: nothing is changed.
     */
    public static function sync(array $modules): bool
    {
        $taskfile = base_path('Taskfile.yml');
        $content = file_exists($taskfile) ? file_get_contents($taskfile) : false;

        if ($content === false || ! preg_match('/^ *# ── BEGIN MODULES.*\n/m', $content, $begin, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $start = $begin[0][1] + strlen($begin[0][0]);
        $end = strpos($content, '    # ── END MODULES', $start);

        if ($end === false) {
            return false;
        }

        $entries = implode('', array_map(self::entry(...), $modules));

        file_put_contents($taskfile, substr($content, 0, $start).$entries.substr($content, $end));

        return true;
    }

    private static function entry(string $module): string
    {
        return "    {$module}:\n        taskfile: ./modules/{$module}/Taskfile.yml\n        optional: true\n";
    }
}
