<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\actes\ICertificateValidationStrategy;
use S2lowLegacy\Class\CurlWrapper;

class NoCertificateValidation implements ICertificateValidationStrategy
{
    public function setUp(CurlWrapper $curlWrapper): void
    {
    }

    public function postConnectionAction(CurlWrapper $curlWrapper): void
    {
    }
}
