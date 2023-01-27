<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\CurlWrapper;
use Exception;
use S2lowLegacy\Lib\X509Certificate;

class ActesFileSender
{
    private $actesMinistereProperties;
    private string $truststorePath;

    public function __construct(
        ActesMinistereProperties $actesMinistereProperties,
        $trustore_path
    ) {
        $this->actesMinistereProperties = $actesMinistereProperties;
        $this->truststorePath = $trustore_path;
    }

    public function send($filepath)
    {
        $curlWrapper = new CurlWrapper();
        $curlWrapper->setTimeout(60, 60*3);

        $url = $this->actesMinistereProperties->url;

        if (mb_substr($url, 0, 5) == 'https') {
            $curlWrapper->setProperties(CURLOPT_SSL_VERIFYHOST, 0);
            $curlWrapper->setProperties(CURLOPT_CERTINFO, 1);
            $curlWrapper->setProperties(CURLOPT_CAPATH, $this->truststorePath);
        }

        if ($this->actesMinistereProperties->adapt_protocol) {
            $curlWrapper->setProperties(
                CURLOPT_SSL_CIPHER_LIST,
                'DEFAULT@SECLEVEL=0 !LOW !MEDIUM !RC4 !aNULL !eNULL !LOW !MD5 !EXP AES256-SHA '
            );
        }

        if ($this->actesMinistereProperties->authentification_type == ActesMinistereProperties::AUTHENTICATION_POST) {
            $url .= "?user={$this->actesMinistereProperties->login}&password={$this->actesMinistereProperties->password}";
        }
        if ($this->actesMinistereProperties->authentification_type == ActesMinistereProperties::AUTHENTICATION_BASIC) {
            $curlWrapper->httpAuthentication($this->actesMinistereProperties->login, $this->actesMinistereProperties->password);
        }

        $curlWrapper->setClientCertificate(
            $this->actesMinistereProperties->client_certificate,
            $this->actesMinistereProperties->client_certificate_key,
            $this->actesMinistereProperties->client_certificate_key_password
        );

        $curlWrapper->addPostFile(basename($filepath), $filepath);

        $curlWrapper->get($url);

        if ($curlWrapper->getHTTPCode() != 200) {
            throw new Exception($curlWrapper->getLastError());
        }

        if (mb_substr($url, 0, 5) == 'https') {
            $x509Certificate = new X509Certificate();

            $actual_certificat = $curlWrapper->getServerCertificate();
            $expected_certificat = file_get_contents($this->actesMinistereProperties->server_certificate_path);

            $actual_hash = $x509Certificate->getBase64Hash($actual_certificat);
            $expected_hash = $x509Certificate->getBase64Hash($expected_certificat);

            if ($actual_hash != $expected_hash) {
                throw new Exception("Le certificat recu ($actual_hash) ne correspond pas à celui attendu ($expected_hash)");
            }
        }

        return true;
    }
}
