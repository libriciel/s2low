<?php

namespace S2lowLegacy\Class;

class CurlWrapperFactory
{
    public function getNewInstance(): CurlWrapper
    {
        return new CurlWrapper();
    }
}
