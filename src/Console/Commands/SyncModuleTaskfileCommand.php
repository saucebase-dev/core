<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use InterNACHI\Modular\Support\ModuleRegistry;
use Saucebase\Core\ModuleTaskfile;

/** Lists exactly the installed modules that have a Taskfile in the app's Taskfile. */
class SyncModuleTaskfileCommand extends Command
{
    protected $signature = 'saucebase:taskfile-sync';

    protected $description = 'List the installed modules in the app\'s Taskfile';

    public function handle(ModuleRegistry $registry): int
    {
        $modules = $registry->modules()
            ->filter(fn ($module): bool => file_exists($module->path('Taskfile.yml')))
            ->map(fn ($module): string => $module->name)
            ->sort()
            ->values()
            ->all();

        if (! ModuleTaskfile::sync($modules)) {
            $this->components->error('Taskfile.yml needs the BEGIN MODULES and END MODULES markers.');

            return self::FAILURE;
        }

        $this->components->info(count($modules).' module(s) listed in Taskfile.yml.');

        return self::SUCCESS;
    }
}
