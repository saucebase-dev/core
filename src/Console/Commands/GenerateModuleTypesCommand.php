<?php

namespace Saucebase\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use InterNACHI\Modular\Support\ModuleRegistry;
use Saucebase\Core\TypeScript\TypeScriptConfig;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\multisearch;

class GenerateModuleTypesCommand extends Command
{
    protected $name = 'module:generate-types';

    protected $description = 'Generate TypeScript types for one or more enabled modules';

    public function __construct()
    {
        parent::__construct();

        $this->getDefinition()->addArgument(
            new InputArgument('module', InputArgument::IS_ARRAY, 'Module name(s) to generate types for')
        );

        $this->getDefinition()->addOption(
            new InputOption('all', 'a', InputOption::VALUE_NONE, 'Generate types for all enabled modules')
        );
    }

    public function handle(): int
    {
        $modules = $this->resolveModules();

        $exitCode = self::SUCCESS;

        foreach ($modules as $name) {
            if ($this->generateForModule($name) === self::FAILURE) {
                $exitCode = self::FAILURE;
            }
        }

        return $exitCode;
    }

    /** @return array<string> */
    private function resolveModules(): array
    {
        $enabledModules = app(ModuleRegistry::class)->modules()->map->name->values()->all();

        if ($this->option('all')) {
            return $enabledModules;
        }

        $given = (array) $this->argument('module');

        if (! empty($given)) {
            return $given;
        }

        $selected = multisearch(
            label: 'Select modules',
            options: function (string $search) use ($enabledModules): array {
                return collect(['All', ...$enabledModules])
                    ->when(strlen($search) > 0, fn (Collection $items) => $items->filter(
                        fn ($item) => str_contains(strtolower($item), strtolower($search))
                    ))
                    ->values()
                    ->toArray();
            },
            required: 'You must select at least one module',
        );

        return in_array('All', $selected) ? $enabledModules : $selected;
    }

    private function generateForModule(string $name): int
    {
        $appPath = module_path($name, 'src');

        if (! is_dir($appPath)) {
            $this->components->error("Module app path not found: {$appPath}");

            return self::FAILURE;
        }

        $typesDir = module_path($name, 'resources/js/types');

        $this->components->task("Generate types · {$name}", function () use ($appPath, $typesDir): bool {
            app()->instance(TypeScriptTransformerConfig::class, TypeScriptConfig::make($appPath, $typesDir));

            try {
                $output = $this->getOutput()->isVerbose() ? $this->output : null;

                return Artisan::call('typescript:transform', [], $output) === self::SUCCESS;
            } finally {
                app()->forgetInstance(TypeScriptTransformerConfig::class);
            }
        });

        return self::SUCCESS;
    }
}
