<?php

namespace S2lowLegacy\Lib;

use S2lowLegacy\Class\TmpFolder;
use Exception;

class RgsCertificate
{
    private $openssl_path;
    private $validca_path;
    private $last_message;

    public function __construct(
        $openssl_path,
        $rgs_validca_path
    ) {
        $this->validca_path = $rgs_validca_path;
        $this->openssl_path = $openssl_path;
    }

    public function getLastMessage()
    {
        return $this->last_message;
    }

    /**
     * @param string $x509_pem_certificate string contenant le certificat à tester
     * @param string|null $clientCertChain string contenant les certificats intermédiaire et racine
     * @return bool
     * @throws Exception
     */

    public function isRgsCertificate(string $x509_pem_certificate, ?string $clientCertChain = null)
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        $tmp_cert = "$tmp_folder/s2low-lib-rgscertificate.pem";
        file_put_contents($tmp_cert, $x509_pem_certificate);

        if ($clientCertChain) {
            $tmp_chain = "$tmp_folder/s2low-lib-certchain.pem";
            file_put_contents($tmp_chain, $clientCertChain);


            $command = "{$this->openssl_path} verify -verbose -untrusted {$tmp_chain} -CApath {$this->validca_path} {$tmp_cert} 2>&1";
            // Explication de la commande sur https://stackoverflow.com/a/26520714/1694298
        } else {
            $command = "{$this->openssl_path} verify -verbose -CApath {$this->validca_path} {$tmp_cert} 2>&1";
        }

        //Il semble qu'il n'y a pas de fonction php openssl_* qui permettent la vérification d'un certificat

        exec($command, $output, $return_var);

        $tmpFolder->delete($tmp_folder);

        $output = implode("\n", $output);

        if (preg_match("#{$tmp_cert}: OK#", $output)) {
            $result = true;
        } else {
            $result = false;
            $this->last_message = $output;
        }

        return $result;
    }
}
