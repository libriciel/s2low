<?php

namespace S2lowLegacy\Class;

use RuntimeException;
use S2low\Kernel;
use S2lowLegacy\Lib\ObjectInstancier;
use Symfony\Component\Dotenv\Dotenv;

/**
 * @deprecated Fin du LegacyObjectsManager. Il faut autowire votre service
 */
class LegacyObjectsManager
{
    private static ObjectInstancier $objectInstancier;
    public static function getLegacyObjectInstancier(): ObjectInstancier
    {
        if (!isset(self::$objectInstancier)) {
            require dirname(__DIR__) . '/vendor/autoload.php';

            (new Dotenv())->bootEnv("/data/config/.env");
            new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
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
