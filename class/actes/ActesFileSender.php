<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Lib\X509Certificate;

class ActesFileSender
{
    private $actesMinistereProperties;
    private string $truststorePath;

    public function __construct(
        ActesMinistereProperties $actesMinistereProperties,
        $trustore_path,
        private readonly CurlWrapperFactory $curlWrapperFactory,
        private readonly X509Certificate $x509Certificate
    ) {
        $this->actesMinistereProperties = $actesMinistereProperties;
        $this->truststorePath = $trustore_path;
    }

    /**
     * @throws Exception
     */
    public function send($filepath)
    {
        $curlWrapper = $this->curlWrapperFactory->getNewInstance();
        $curlWrapper->setTimeout(60, 60 * 3);

        if ($this->actesMinistereProperties->isHttps()) {
            $this->setUpForPostTransfertCertificateValidation($curlWrapper);
        }

        if ($this->actesMinistereProperties->adapt_protocol) {
            $this->setupForAdaptedProtocol($curlWrapper);
        }

        if ($this->actesMinistereProperties->authentification_type == ActesMinistereProperties::AUTHENTICATION_BASIC) {
            $curlWrapper->httpAuthentication(
                $this->actesMinistereProperties->login,
                $this->actesMinistereProperties->password
            );
        }

        $curlWrapper->setClientCertificate(
            $this->actesMinistereProperties->client_certificate,
            $this->actesMinistereProperties->client_certificate_key,
            $this->actesMinistereProperties->client_certificate_key_password
        );

        $curlWrapper->addPostFile(basename($filepath), $filepath);

        $curlWrapper->get($this->actesMinistereProperties->getUrl());

        if ($curlWrapper->getHTTPCode() != 200) {
            throw new Exception($curlWrapper->getLastError());
        }

        if ($this->actesMinistereProperties->isHttps()) {
            $this->postTransfertCertificateValidation($curlWrapper);
        }

        return true;
    }

    /**
     * @param CurlWrapper $curlWrapper
     * @return void
     */
    private function setUpForPostTransfertCertificateValidation(CurlWrapper $curlWrapper): void
    {
        $curlWrapper->setProperties(CURLOPT_SSL_VERIFYHOST, 0);
        $curlWrapper->setProperties(CURLOPT_CERTINFO, 1);
        $curlWrapper->setProperties(CURLOPT_CAPATH, $this->truststorePath);
    }

    /**
     * @param CurlWrapper $curlWrapper
     * @return void
     */
    private function setupForAdaptedProtocol(CurlWrapper $curlWrapper): void
    {
        $curlWrapper->setProperties(
            CURLOPT_SSL_CIPHER_LIST,
            'DEFAULT@SECLEVEL=0 !LOW !MEDIUM !RC4 !aNULL !eNULL !LOW !MD5 !EXP AES256-SHA '
        );
    }

    /**
     * @param CurlWrapper $curlWrapper
     * @return void
     * @throws Exception
     */
    private function postTransfertCertificateValidation(CurlWrapper $curlWrapper): void
    {
        $actual_certificat = $curlWrapper->getServerCertificate();
        $expected_certificat = file_get_contents($this->actesMinistereProperties->server_certificate_path);

        $actual_hash = $this->x509Certificate->getBase64Hash($actual_certificat);
        $expected_hash = $this->x509Certificate->getBase64Hash($expected_certificat);

        if ($actual_hash != $expected_hash) {
            throw new Exception("Le certificat recu ($actual_hash) ne correspond pas à celui attendu ($expected_hash)");
        }
    }
}
