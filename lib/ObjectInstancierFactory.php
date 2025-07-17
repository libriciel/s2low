<?php

namespace S2lowLegacy\Lib;

/**
 * @deprecated Fin du ObjectInstancier. Il faut autowire votre service
 */
class ObjectInstancierFactory
{
    /** @var  ObjectInstancier */
    private static ObjectInstancier $objectInstancier;

    public static function setObjectInstancier(ObjectInstancier $objectInstancier): void
    {
        self::$objectInstancier = $objectInstancier;
    }

    public static function getObjetInstancier()
    {
        return self::$objectInstancier;
    }

    public static function resetObjectInstancier(): void
    {
//
    }
}
