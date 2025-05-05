<?php

namespace S2low\Factory;

use S2lowLegacy\Lib\SessionWrapper;

class SessionWrapperFactory
{
    public static function create(): SessionWrapper
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        $session =& $_SESSION;

        return new SessionWrapper($session);
    }
}
