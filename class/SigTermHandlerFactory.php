<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\SigTermHandler;

class SigTermHandlerFactory
{
    public function getInstance()
    {
        return SigTermHandler::getInstance();
    }
}
