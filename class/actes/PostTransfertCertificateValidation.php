<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Lib\X509Certificate;

class PostTransfertCertificateValidation implements ICertificateValidationStrategy
{
    public function __construct(
        private readonly string $server_certificate_path,
        private readonly string $truststorePath,
        private readonly X509Certificate $x509Certificate
    ) {
    }

    public function setUp(CurlWrapper $curlWrapper): void
    {
        $curlWrapper->setProperties(CURLOPT_SSL_VERIFYHOST, 0);
        $curlWrapper->setProperties(CURLOPT_CERTINFO, 1);
        $curlWrapper->setProperties(CURLOPT_CAPATH, $this->truststorePath);
    }

    /**
     * @throws Exception
     */
    public function postConnectionAction(CurlWrapper $curlWrapper): void
    {
        $actual_certificat = $curlWrapper->getServerCertificate();
        $expected_certificat = file_get_contents($this->server_certificate_path);

        $actual_hash = $this->x509Certificate->getBase64Hash($actual_certificat);
        $expected_hash = $this->x509Certificate->getBase64Hash($expected_certificat);

        if ($actual_hash != $expected_hash) {
            // TODO : modifier l'exception pour une exceptions dédiée
            throw new Exception(
                "Le certificat recu ($actual_hash) ne correspond pas à celui attendu ($expected_hash)"
            );
        }
    }
}
