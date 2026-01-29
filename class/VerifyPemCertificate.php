<?php

namespace S2lowLegacy\Class;

use Exception;
use S2low\Services\ProcessCommand\OpenSSLWrapper;

class VerifyPemCertificate
{
    # extracted from https://github.com/openssl
    # Mise en correspondance de  openssl/crypto/x509/x509_txt.c
    # et https://docs.huihoo.com/doxygen/openssl/1.0.1c/crypto_2x509_2x509__vfy_8h.html
    public const CERTIFICATE_CHAIN_ERRORS = array(
        2,  # unable to get issuer certificate
        3,  # unable to get certificate CRL
        18, # self signed certificate
        19, # self signed certificate in certificate chain
        20, # unable to get local issuer certificate
        21, # unable to verify the first certificate
    );
    /** @var string */
    private $openSSLWrapper;

    public function __construct(
        OpenSSLWrapper $openSSLWrapper
    ) {
        $this->openSSLWrapper = $openSSLWrapper;
    }

    /**
     * @throws Exception
     */
    public function checkCertificateWithOpenSSL(
        $certificate_path,
        string $authorized_ca_path,
        array $filteredErrors = [],
        ?string $timestamp = null,
    ): bool {
        $this->checkForCrlRevocation($certificate_path, $authorized_ca_path);
        $this->openSSLWrapper->verify($certificate_path, $authorized_ca_path, $filteredErrors, $timestamp);
        return true;
    }

    /**
     * @param string $file
     * @param string $authorized_ca_path
     * @return void
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    private function checkForCrlRevocation(string $file, string $authorized_ca_path): void
    {
        $file_r0_name = $this->openSSLWrapper->extractHash($file);
        $file_r0 = $authorized_ca_path . "/$file_r0_name.r0";
        if (file_exists($file_r0)) {
            // 1) extraire le SN du certificat
            $serialNumber = $this->openSSLWrapper->extractCertificateSN($file);
            // 2) vérifier que ce SN n'est pas présent dans la CRL (Pour l'instant, la date n'est pas prise en compte)
            // On ne vérifie pas
            // 1) la date
            // 2) si la CRL garde bien les certificats expirés ( extension 2.5.29.60 )
            $this->openSSLWrapper->checkSNIsInCRL($file_r0, $serialNumber);
        }
    }
}
