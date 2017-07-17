<?php

class ObjectInstancierFactory {

    /** @var  ObjectInstancier */
    private static $objetInstancier;

    public static function setObjectInstancier(ObjectInstancier $objectInstancier){
        self::$objetInstancier = $objectInstancier;
    }

    public static function getObjetInstancier(){
        return self::$objetInstancier;
    }

}