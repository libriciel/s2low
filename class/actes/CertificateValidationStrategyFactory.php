<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Lib\X509Certificate;

class CertificateValidationStrategyFactory
{
    public function __construct(
        private readonly ActesMinistereProperties $actesMinistereProperties,
        private readonly string $trustore_path,
        private readonly X509Certificate $x509Certificate
    ) {
    }
    public function getInstance(): ICertificateValidationStrategy
    {
        if (!$this->actesMinistereProperties->isHttps()) {
            return new NoCertificateValidation();
        }
        if ($this->actesMinistereProperties->use_legacy_protocol) {
            return new PostTransfertCertificateValidation(
                $this->actesMinistereProperties->server_certificate_path,
                $this->trustore_path,
                $this->x509Certificate
            );
        }
        return new PreTransfertCertificateValidation(
            $this->trustore_path
        );
    }
}
