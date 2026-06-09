<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use Exception;
use S2lowLegacy\Class\PublicKeyExtractor;

class ActesFileSender
{
    private ?string $serverPublicKeyHash = null;

    public function __construct(
        private readonly ActesMinistereProperties $actesMinistereProperties,
        private readonly string $truststorePath,
        private readonly CurlWrapperFactory $curlWrapperFactory
    ) {
    }

    private function isHttps(): bool
    {
        return str_starts_with($this->actesMinistereProperties->url, 'https');
    }

    private function hasClientCertificate(): bool
    {
        return !empty($this->actesMinistereProperties->client_certificate);
    }

    private function getClientCertificateArray(): array
    {
        return [
            $this->actesMinistereProperties->client_certificate,
            $this->actesMinistereProperties->client_certificate_key,
            $this->actesMinistereProperties->client_certificate_key_password
        ];
    }

    private function getServerPublicKeyHash(): string
    {
        if ($this->serverPublicKeyHash === null) {
            $publicKeyExtractor = new PublicKeyExtractor();
            $this->serverPublicKeyHash = $publicKeyExtractor->getPublicKeyHashFromCertificatePath(
                $this->actesMinistereProperties->server_certificate_path
            );
        }
        return $this->serverPublicKeyHash;
    }

    /**
     * @throws Exception
     */
    public function send(string $filepath): bool
    {
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

        return $this->executeRequest($curlWrapper, $url, 200);
    }

    private function getPreparedCurlWrapper(int $timeout, int $connectTimeout = 60): CurlWrapper
    {
        $curlWrapper = $this->curlWrapperFactory->getNewInstance();
        $curlWrapper->setTimeout($connectTimeout, $timeout);
        $curlWrapper->setProperties(CURLOPT_USERAGENT, 'curl/7.81.1');

        if ($this->isHttps()) {
            $curlWrapper->setHttpsConnexionWithVerifPeerPubKey($this->getServerPublicKeyHash());
        } else {
            $curlWrapper->setProperties(CURLOPT_CERTINFO, 1);
        }

        if ($this->hasClientCertificate()) {
            $curlWrapper->setClientCertificate(...$this->getClientCertificateArray());
        }

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
        } elseif (!$this->isHttps()) {
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
    private function executeRequest(CurlWrapper $curlWrapper, string $url, int $expectedHttpCode): bool
    {
        $curlWrapper->get($url);

        if ($curlWrapper->getHTTPCode() !== $expectedHttpCode) {
            throw new Exception($curlWrapper->getLastError());
        }

        return true;
    }
}
