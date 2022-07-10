<?php

class ObjectInstancierFactory
{
    /** @var  ObjectInstancier */
    private static $objetInstancier;

    public static function setObjectInstancier(ObjectInstancier $objectInstancier)
    {
        self::$objetInstancier = $objectInstancier;
    }

    public static function getObjetInstancier()
    {
        return self::$objetInstancier;
    }

    public static function issetObjectInstancier(): bool
    {
        return isset(self::$objetInstancier);
    }

    public static function resetObjectInstancier(): void
    {
        self::$objetInstancier = null;
    }
}
