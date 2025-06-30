<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\ObjectInstancier;

/**
 * @deprecated Fin du DatabasePool. Il faut autowire Database::class
 */
class DatabasePool
{
    private static $objectInstancier;

    public static function setObjectInstancier(ObjectInstancier $objectInstancier): void
    {
        self::$objectInstancier = $objectInstancier;
    }

    public static function getInstance()
    {
        return self::$objectInstancier->get(Database::class);
    }
}
