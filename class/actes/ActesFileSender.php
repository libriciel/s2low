<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\CurlWrapper;
use Exception;
use S2lowLegacy\Lib\X509Certificate;

class ActesFileSender
{
    private ActesMinistereProperties $actesMinistereProperties;
    private string $truststorePath;

    public function __construct(
        ActesMinistereProperties $actesMinistereProperties,
        string $trustore_path
    ) {
        $this->actesMinistereProperties = $actesMinistereProperties;
        $this->truststorePath = $trustore_path;
    }

    /**
     * @throws Exception
     */
    public function send(string $filepath): bool
    {
        $this->verifyCertificate();

        if ($this->actesMinistereProperties->use_legacy_protocol) {
            return $this->sendLegacy($filepath);
        }

        $url = $this->actesMinistereProperties->url;
        $curlWrapper = $this->getPreparedCurlWrapper(60 * 3);
        $this->configureSSL($curlWrapper, false);

        $curlWrapper->addPostFile(basename($filepath), $filepath);

        return $this->executeRequest($curlWrapper, $url, 201);
    }

    /**
     * @throws Exception
     */
    public function sendLegacy(string $filepath): bool
    {
        $url = $this->actesMinistereProperties->url;
        $curlWrapper = $this->getPreparedCurlWrapper(60);

        $this->configureSSL($curlWrapper, true);
        $this->configureAuthentication($curlWrapper, $url);

        $curlWrapper->addPostFile(basename($filepath), $filepath);

        $this->verifyCertificate();

        return $this->executeRequest($curlWrapper, $url, 200);
    }

    /**
     * @throws Exception
     */
    private function verifyCertificate(): void
    {
        $url = $this->actesMinistereProperties->url;
        if (mb_substr($url, 0, 5) !== 'https') {
            return;
        }

        $curlWrapper = $this->getPreparedCurlWrapper(10, 10);
        $curlWrapper->setProperties(CURLOPT_NOBODY, true);

        $useLegacy = $this->actesMinistereProperties->use_legacy_protocol;
        $this->configureSSL($curlWrapper, $useLegacy);

        if ($useLegacy) {
            $this->configureAuthentication($curlWrapper, $url);
        }

        $curlWrapper->get($url);

        $this->checkCertificateHash($curlWrapper->getServerCertificate());
    }

    private function getPreparedCurlWrapper(int $timeout, int $connectTimeout = 60): CurlWrapper
    {
        $curlWrapper = new CurlWrapper();
        $curlWrapper->setTimeout($connectTimeout, $timeout);
        $curlWrapper->setProperties(CURLOPT_USERAGENT, 'curl/7.81.1');
        $curlWrapper->setProperties(CURLOPT_CERTINFO, 1);

        $curlWrapper->setClientCertificate(
            $this->actesMinistereProperties->client_certificate,
            $this->actesMinistereProperties->client_certificate_key,
            $this->actesMinistereProperties->client_certificate_key_password
        );

        return $curlWrapper;
    }

    private function configureSSL(CurlWrapper $curlWrapper, bool $useLegacy): void
    {
        $curlWrapper->setProperties(CURLOPT_CAPATH, $this->truststorePath);

        if ($useLegacy) {
            $curlWrapper->setProperties(CURLOPT_SSL_VERIFYHOST, 0);
            if ($this->actesMinistereProperties->adapt_protocol) {
                $curlWrapper->setProperties(
                    CURLOPT_SSL_CIPHER_LIST,
                    'DEFAULT@SECLEVEL=0 !LOW !MEDIUM !RC4 !aNULL !eNULL !LOW !MD5 !EXP AES256-SHA '
                );
            }
        } else {
            $curlWrapper->setProperties(CURLOPT_SSL_VERIFYPEER, 1);
            $curlWrapper->setProperties(CURLOPT_SSL_VERIFYHOST, 2);
        }
    }

    private function configureAuthentication(CurlWrapper $curlWrapper, string &$url): void
    {
        if ($this->actesMinistereProperties->authentification_type === ActesMinistereProperties::AUTHENTICATION_POST) {
            $url .= "?user={$this->actesMinistereProperties->login}&password={$this->actesMinistereProperties->password}";
        }
        if ($this->actesMinistereProperties->authentification_type === ActesMinistereProperties::AUTHENTICATION_BASIC) {
            $curlWrapper->httpAuthentication(
                $this->actesMinistereProperties->login,
                $this->actesMinistereProperties->password
            );
        }
    }

    /**
     * @throws Exception
     */
    private function checkCertificateHash(string $actualCertificate): void
    {
        $expectedPath = $this->actesMinistereProperties->server_certificate_path;
        if (!$expectedPath) {
            return;
        }

        $x509Certificate = new X509Certificate();
        $actualHash = $x509Certificate->getBase64Hash($actualCertificate);
        $expectedHash = $x509Certificate->getBase64Hash(file_get_contents($expectedPath));

        if ($actualHash !== $expectedHash) {
            throw new Exception("Le certificat reçu ($actualHash) ne correspond pas à celui attendu ($expectedHash)");
        }
    }

    /**
     * @throws Exception
     */
    private function executeRequest(CurlWrapper $curlWrapper, string $url, int $expectedHttpCode): bool
    {
        $curlWrapper->get($url);

        if ($curlWrapper->getHTTPCode() !== $expectedHttpCode) {
            throw new Exception($curlWrapper->getLastError());
        }

        return true;
    }
}
