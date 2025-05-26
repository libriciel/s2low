<?php

namespace S2lowLegacy\Class\actes;

use JsonSchema\Uri\Retrievers\Curl;
use S2lowLegacy\Class\CurlWrapper;

interface ICertificateValidationStrategy
{
    public function setUp(CurlWrapper $curlWrapper): void;
    public function postConnectionAction(CurlWrapper $curlWrapper): void;
}
