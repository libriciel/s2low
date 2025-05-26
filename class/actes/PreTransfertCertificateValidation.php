<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\CurlWrapper;

class PreTransfertCertificateValidation implements ICertificateValidationStrategy
{
    public function __construct(private readonly string $truststorePath)
    {
    }

    public function setUp(CurlWrapper $curlWrapper): void
    {
        $curlWrapper->setProperties(CURLOPT_SSL_VERIFYHOST, 2);
        $curlWrapper->setProperties(CURLOPT_CERTINFO, 1);
        $curlWrapper->setProperties(CURLOPT_CAPATH, $this->truststorePath);
        $curlWrapper->setProperties(CURLOPT_USERAGENT, 'curl/*');
    }

    public function postConnectionAction(CurlWrapper $curlWrapper): void
    {
    }
}
