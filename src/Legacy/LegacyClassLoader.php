<?php

declare(strict_types=1);

namespace S2low\Legacy;

use S2low\Tests\Legacy\S2lowLegacyCommand;

class LegacyClassLoader
{
    public function getClassName(string $file): string
    {
        $classes = get_declared_classes();
        ob_start();
        include $file;
        ob_end_clean();
        $diff = array_diff(get_declared_classes(), $classes);
        $class = reset($diff);
        if (!is_subclass_of($class, S2lowLegacyCommand::class)) {
            return '';
        }
        return $class;
    }
}
