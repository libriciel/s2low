<?php

namespace S2lowLegacy\Class\Helpers;

class SessionHelper
{
    public static function getFromSession($name, $delete = true)
    {
        $ret = null;
        if (isset($_SESSION["temp"][$name])) {
            $ret = $_SESSION["temp"][$name];
        }

        if ($delete) {
            unset($_SESSION["temp"][$name]);
        }

        return $ret;
    }

    public static function putInSession($name, $value)
    {
        $_SESSION["temp"][$name] = $value;
    }

    public static function purgeTempSession()
    {
        unset($_SESSION["temp"]);
    }
}
