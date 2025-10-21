<?php

namespace S2lowLegacy\Lib;

use S2low\DTO\CertificateAnalysis;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\TmpFolder;
use Exception;

class RgsCertificate
{
    private $validca_path;

    public function __construct(
        string $validca_path
    ) {
        $this->validca_path = $validca_path;
    }

    /**
     * @param string $x509_pem_certificate string contenant le certificat à tester
     * @param string|null $clientCertChain string contenant les certificats intermédiaire et racine
     * @return \S2low\DTO\CertificateAnalysis
     * @throws \Exception
     */

    public function isRgsCertificate(string $x509_pem_certificate, string $clientCertChain = null): CertificateAnalysis
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        $tmp_cert = "$tmp_folder/s2low-lib-rgscertificate.pem";
        file_put_contents($tmp_cert, $x509_pem_certificate);

        if ($clientCertChain) {
            $tmp_chain = "$tmp_folder/s2low-lib-certchain.pem";
            file_put_contents($tmp_chain, $clientCertChain);


            $command = OpenSSLWrapper::PATH . " verify -verbose -untrusted {$tmp_chain} -CApath {$this->validca_path} {$tmp_cert} 2>&1";
            // Explication de la commande sur https://stackoverflow.com/a/26520714/1694298
        } else {
            $command = OpenSSLWrapper::PATH . " verify -verbose -CApath {$this->validca_path} {$tmp_cert} 2>&1";
        }

        //Il semble qu'il n'y a pas de fonction php openssl_* qui permettent la vérification d'un certificat

        exec($command, $output, $return_var);

        $tmpFolder->delete($tmp_folder);

        $output = implode("\n", $output);

        if (preg_match("#{$tmp_cert}: OK#", $output)) {
            return new CertificateAnalysis(true);
        }
        return new CertificateAnalysis(false, $output);
    }

    public function hasSSlClientPurpose(string $certificate): bool
    {
        return openssl_x509_checkpurpose($certificate, X509_PURPOSE_SSL_CLIENT, [$this->validca_path]) === true;
    }
}
