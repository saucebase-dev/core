<?php

namespace Saucebase\Core\Support;

final class DependencyOrder
{
    /**
     * The items, each after the ones it depends on. Dependencies outside the
     * list are ignored, and a cycle stops at the item it comes back to.
     *
     * @param  string[]  $items
     * @param  array<string, string[]>  $dependencies
     * @return list<string>
     */
    public static function sort(array $items, array $dependencies): array
    {
        $ordered = [];
        $visiting = [];
        $visit = function (string $item) use (&$visit, &$ordered, &$visiting, $items, $dependencies): void {
            if (in_array($item, $ordered, true) || isset($visiting[$item])) {
                return;
            }

            $visiting[$item] = true;
            foreach (array_intersect($dependencies[$item] ?? [], $items) as $dependency) {
                $visit($dependency);
            }
            $ordered[] = $item;
        };

        foreach (array_unique($items) as $item) {
            $visit($item);
        }

        return $ordered;
    }
}
