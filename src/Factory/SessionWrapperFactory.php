<?php

namespace S2low\Factory;

use S2lowLegacy\Lib\SessionWrapper;

class SessionWrapperFactory
{
    public static function create(): SessionWrapper
    {
        $session = $_SESSION ?? [];
        return new SessionWrapper($session);
    }
}
