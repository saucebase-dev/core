<?php

namespace Saucebase\Core;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\ModuleConfig;
use RuntimeException;

/**
 * Stops the boot when a folder in modules/ isn't autoloaded by Composer.
 *
 * internachi/modular loads every folder there, installed or not. One Composer doesn't map
 * (copied in before `composer require`, left behind by `composer remove`, a psr-4 typo) has
 * routes naming classes nothing can load, and fails with an unrelated error. This names the
 * folder and the fix instead. It never skips the module: a missing module is worse than a
 * loud one.
 */
class UnloadableModules
{
    /**
     * @param  Collection<array-key, ModuleConfig>  $modules
     *
     * @throws RuntimeException
     */
    public static function refuse(Collection $modules): void
    {
        $loaders = collect(ClassLoader::getRegisteredLoaders());
        $mapped = $loaders->flatMap(fn (ClassLoader $loader): array => array_keys($loader->getPrefixesPsr4()))->flip();

        $unloadable = $modules
            ->reject(fn (ModuleConfig $module): bool => $module->namespaces->contains(
                fn (string $namespace): bool => $mapped->has($namespace) || self::inClassmap($loaders, $namespace),
            ))
            ->map(fn (ModuleConfig $module): string => "modules/{$module->name}");

        if ($unloadable->isNotEmpty()) {
            throw new RuntimeException(
                'Composer does not autoload '.$unloadable->implode(', ').'. '
                .'Install it with `composer require <vendor>/<module>`, or remove the folder.'
            );
        }
    }

    /**
     * A classmap (a root `"classmap": ["modules/"]`, or an optimized autoloader) loads a module
     * without its psr-4 prefix. Only reached for a namespace no psr-4 prefix covers.
     *
     * @param  Collection<array-key, ClassLoader>  $loaders
     */
    private static function inClassmap(Collection $loaders, string $namespace): bool
    {
        return $loaders->contains(fn (ClassLoader $loader): bool => array_any(
            array_keys($loader->getClassMap()),
            fn (string $class): bool => str_starts_with($class, $namespace),
        ));
    }
}
