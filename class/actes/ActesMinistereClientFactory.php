<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Lib\X509Certificate;

class ActesMinistereClientFactory
{
    public function __construct(
        private readonly ActesMinistereProperties $actesMinistereProperties,
        private readonly string $trustore_path,
        private readonly CurlWrapperFactory $curlWrapperFactory,
        private readonly X509Certificate $x509Certificate
    ) {
    }
    public function get(): ActesMinistereClient
    {
        $curlWrapper = $this->curlWrapperFactory->getNewInstance();
        $curlWrapper->setTimeout(60, 60 * 3);

        $certificateValidation = $this->getCertificateValidationStrategy();

        $certificateValidation->setUp($curlWrapper);
        $this->setAdaptationProtocol($curlWrapper);
        $this->configureAuthSettings($curlWrapper);

        $curlWrapper->setClientCertificate(
            $this->actesMinistereProperties->client_certificate,
            $this->actesMinistereProperties->client_certificate_key,
            $this->actesMinistereProperties->client_certificate_key_password
        );
        return new ActesMinistereClient(
            $this->actesMinistereProperties->getUrl(),
            $this->actesMinistereProperties->getSuccessHttpCode(),
            $curlWrapper,
            $certificateValidation
        );
    }

    private function configureAuthSettings(CurlWrapper $curlWrapper): void
    {
        if ($this->actesMinistereProperties->authentification_type === ActesMinistereProperties::AUTHENTICATION_BASIC) {
            $curlWrapper->httpAuthentication(
                $this->actesMinistereProperties->login,
                $this->actesMinistereProperties->password
            );
        }
    }

    private function getCertificateValidationStrategy(): ICertificateValidationStrategy
    {
        if (!$this->actesMinistereProperties->isHttps()) {
            return new NoCertificateValidationStrategy();
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

    private function setAdaptationProtocol(CurlWrapper $curlWrapper): void
    {
        if ($this->actesMinistereProperties->adapt_protocol) {
            $curlWrapper->setProperties(
                CURLOPT_SSL_CIPHER_LIST,
                'DEFAULT@SECLEVEL=0 !LOW !MEDIUM !RC4 !aNULL !eNULL !LOW !MD5 !EXP AES256-SHA '
            );
        }
    }
}
