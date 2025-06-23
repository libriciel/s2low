<?php

namespace S2lowLegacy\Class;

use RuntimeException;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\ObjectInstancierFactory;

/**
 * @deprecated Fin du ObjectInstancier. Il faut autowire votre service
 */
class LegacyObjectsManager
{
    private static ObjectInstancier $objectInstancier;
    public static function getLegacyObjectInstancier(): ObjectInstancier
    {
        if (!isset(self::$objectInstancier)) {
            throw new RuntimeException('ObjectInstancier not initialized');
        }

        return self::$objectInstancier;
    }

    public static function setObjectInstancier(ObjectInstancier $objectInstancier): void
    {
        self::$objectInstancier = $objectInstancier;
    }

    public static function setLegacyObjectInstancier(): void
    {
//
    }

    public static function resetObjectInstancier()
    {
//
    }

    /**
     * @deprecated Fin du LegacyObjectManager. Il faut autowire votre service
     */
    public static function getObject(string $className)
    {
        if (!isset(self::$objectInstancier)) {
            throw new RuntimeException('ObjectInstancier not initialized');
        }

        return self::$objectInstancier->get($className);
    }
}
