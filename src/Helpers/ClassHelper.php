<?php

namespace S2low\Helpers;

//thanks https://stackoverflow.com/a/56995448/1694298

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ClassHelper
{
    public static function findRecursive(string $namespace): array
    {
        $namespacePath = self::translateNamespacePath($namespace);
        if ($namespacePath === '') {
            return [];
        }

        return self::searchClasses($namespace, $namespacePath);
    }

    protected static function translateNamespacePath(string $namespace): string
    {
        $rootPath = __DIR__ . "/../".DIRECTORY_SEPARATOR;

        $nsParts = explode('\\', $namespace);
        array_shift($nsParts);

        if (empty($nsParts)) {
            return '';
        }
        return realpath($rootPath. implode(DIRECTORY_SEPARATOR, $nsParts)) ?: '';
    }

    private static function searchClasses(string $namespace, string $namespacePath): array
    {
        $classes = [];

        /**
         * @var RecursiveDirectoryIterator $iterator
         * @var SplFileInfo $item
         */
        foreach ($iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($namespacePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            if ($item->isDir()) {
                $nextPath = $iterator->current()->getPathname();
                $nextNamespace = $namespace . '\\' . $item->getFilename();
                $classes = array_merge($classes, self::searchClasses($nextNamespace, $nextPath));
                continue;
            }
            if ($item->isFile() && $item->getExtension() === 'php') {
                $class = $namespace . '\\' . $item->getBasename('.php');
                if (!class_exists($class)) {
                    continue;
                }
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
