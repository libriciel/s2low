<?php

namespace S2lowLegacy\Lib;

use Exception;
use S2lowLegacy\Model\UserSQL;
use OpenSSLCertificate;

class X509Certificate
{
    public function retrieveClientInfo()
    {
         // Ne marche pas avec apache-ssl
        if (empty($_SERVER['SSL_CLIENT_VERIFY'])) {
            return false;
        }
        if ($_SERVER['SSL_CLIENT_VERIFY'] != "SUCCESS") {
            return false;
        }

        if (empty($_SERVER['SSL_CLIENT_CERT'])) {
            return false;
        }

        try {
            $info = $this->getInfo($_SERVER['SSL_CLIENT_CERT']);
        } catch (Exception $e) {
            return false;
        }

        $result['issuer'] = $info['issuer_name'];
        $result['subject'] = $info['subject_name'];
        $result['certificate_hash'] = $info['certificate_hash'];
        return $result;
    }

    /**
     * @param $pem_certificate_content
     * @return array|bool
     * @throws Exception
     */
    public function getInfo($pem_certificate_content)
    {
        if (! $pem_certificate_content) {
            return false;
        }
        $resource = $this->readCertContent($pem_certificate_content);
        $info =  openssl_x509_parse($resource);
        $info['expiration_date'] = $this->certTime2IsoDate($info['validTo_time_t']);
        $info['issuer_name'] = $this->linearizeCertInfo($info['issuer']);
        $info['subject_name'] = $this->linearizeCertInfo($info['subject']);
        $info['certificate_hash'] = $this->getBase64Hash($pem_certificate_content, UserSQL::CERTIFICATE_FINGERPRINT_HASH_ALG);
        return $info;
    }

    private function linearizeCertInfo(array $info)
    {
        $result = "";
        foreach ($info as $key => $val) {
            if (is_array($val)) {
                $val = legacy_encode_array($val);
                $val = implode(",", $val);
            }
            $result .= "/$key=$val";
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public function getBase64Hash($cert_content, $hash_alg = 'sha1')
    {
        $search = ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", " "];
        $der_content = base64_decode(str_replace($search, '', $cert_content));

        if (!$der_content) {
            return null; // Comportement Legacy
        }
        $hash_binary = hash($hash_alg, $der_content, true);

        return base64_encode($hash_binary);
    }

    /**
     * @param $pem_certificate_content
     * @return bool|mixed
     * @throws Exception
     */
    public function getExpirationDate($pem_certificate_content)
    {
        $info = $this->getInfo($pem_certificate_content);
        if (! $info) {
            return false;
        }
        return $info['expiration_date'];
    }

    /**
     * @param $cert_content
     * @return OpenSSLCertificate
     * @throws Exception
     */
    private function readCertContent($cert_content): OpenSSLCertificate
    {
        @ $resource = openssl_x509_read($cert_content);
        if (! $resource) {
            throw new Exception("Impossible de lire le certificat");
        }
        return $resource;
    }

    private function certTime2IsoDate($validTo)
    {
        return date("Y-m-d H:i:s", $validTo);
    }

    /**
     * @param $pem_certificate_content
     * @param bool $strtoupper
     * @return string
     * @throws Exception
     */
    public function getIssuerDN($pem_certificate_content, $strtoupper = false)
    {
        $info = $this->getInfo($pem_certificate_content);

        $issuerName = [];
        foreach (array_reverse($info['issuer']) as $document_id => $value) {
            if ($strtoupper) {
                $issuerName[] = mb_strtoupper($document_id) . "=$value";
            } else {
                $issuerName[] = "$document_id=$value";
            }
        }
        return implode(", ", $issuerName);
    }

    /**
     * @param $not_clean_pem
     * @return mixed
     * @throws Exception
     */
    public function pemClean($not_clean_pem)
    {
        $resource = $this->readCertContent($not_clean_pem);
        openssl_x509_export($resource, $output);
        return $output;
    }
}
