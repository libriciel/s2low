<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\CurlWrapper;

class ActesMinistereClientFactory
{
    public function __construct(
        private readonly ActesMinistereProperties $actesMinistereProperties,
        private readonly CurlWrapper $curlWrapper,
        private readonly ICertificateValidationStrategy $certificateValidationStrategy
    ) {
    }
    public function get(): ActesMinistereClient
    {
        $this->curlWrapper->setTimeout(60, 60 * 3);

        $this->certificateValidationStrategy->setUp($this->curlWrapper);
        $this->setAdaptationProtocol($this->curlWrapper);
        $this->configureAuthSettings($this->curlWrapper);

        $this->curlWrapper->setClientCertificate(
            $this->actesMinistereProperties->client_certificate,
            $this->actesMinistereProperties->client_certificate_key,
            $this->actesMinistereProperties->client_certificate_key_password
        );
        return new ActesMinistereClient(
            $this->actesMinistereProperties->getUrl(),
            $this->actesMinistereProperties->getSuccessHttpCode(),
            $this->curlWrapper,
            $this->certificateValidationStrategy
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
