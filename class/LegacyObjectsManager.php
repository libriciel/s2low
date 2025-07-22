<?php

namespace S2lowLegacy\Class;

use S2low\Factory\KernelFactory;
use S2lowLegacy\Lib\ObjectInstancier;

/**
 * @deprecated Fin du LegacyObjectsManager. Il faut autowire votre service
 */
class LegacyObjectsManager
{
    private static ObjectInstancier $objectInstancier;

    public static function setObjectInstancier(ObjectInstancier $objectInstancier): void
    {
        self::$objectInstancier = $objectInstancier;
    }

    public static function getLegacyObjectInstancier(): ObjectInstancier
    {
        self::ensureKernelIsUp();

        return self::$objectInstancier;
    }

    public static function ensureKernelIsUp(): void
    {
        $kernelIsNotUp = !isset(self::$objectInstancier); // Egale car le kernel set l'ObjectInstancier dans son construct.

        if ($kernelIsNotUp) {
            KernelFactory::start();
        }
    }

    /**
     * @deprecated Fin du LegacyObjectManager. Il faut autowire votre service
     */
    public static function getObject(string $className)
    {
        self::ensureKernelIsUp();

        return self::$objectInstancier->get($className);
    }

    public static function setLegacyObjectInstancier(): void
    {
        self::ensureKernelIsUp();
    }

    public static function resetObjectInstancier()
    {
//
    }
}
