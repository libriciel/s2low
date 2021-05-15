<?php


class VerifyPemCertificate
{
    # extracted from https://github.com/openssl
    # Mise en correspondance de  openssl/crypto/x509/x509_txt.c
    # et https://docs.huihoo.com/doxygen/openssl/1.0.1c/crypto_2x509_2x509__vfy_8h.html
    const CERTIFICATE_CHAIN_ERRORS = array (
        2,  # unable to get issuer certificate
        3,  # unable to get certificate CRL
        18, # self signed certificate
        19, # self signed certificate in certificate chain
        20, # unable to get local issuer certificate
        21, # unable to verify the first certificate
    );

    public function __construct($authorized_ca_path){
        $this->authorized_ca_path = $authorized_ca_path;
    }

    /**
     * @param $certificate_path
     * @param string|null $timestamp
     * @return array
     */
    private function launchOpenSslVerify($certificate_path, string $timestamp =null): array
    {
        $erreurs = [];
        $verifyCmd = "openssl verify -CApath {$this->authorized_ca_path} -crl_check $certificate_path 2>&1";

        if($timestamp){
            $verifyCmd = "openssl verify -CApath {$this->authorized_ca_path} -attime $timestamp -crl_check $certificate_path 2>&1";
        }
        exec($verifyCmd, $out, $ret);

        foreach ($out as $line) {
            if (preg_match("/error ([0123456789]+) at ([0123456789])+ depth lookup:(.*)/", $line, $matches)) {
                $erreurs[]=[
                    "errorCode"=>$matches[1],
                    "depth"=>$matches[2],
                    "message"=>$matches[3]
                ];
            }
        }
        return $erreurs;
    }

    /**
     * @throws \Exception
     */
    public function checkCertificateWithOpenSSL($certificate_path, array $filteredErrors = [], string $timestamp = null ): bool
    {
        $erreursVerifyOpenSsl =  $this->launchOpenSslVerify($certificate_path,$timestamp);
        $this->checkForBlockingVerifyErrors($erreursVerifyOpenSsl, $filteredErrors);

        return true;
    }

    /**
     * @param array $errors
     * @param array $nonBlockingErrors
     * @throws Exception
     */
    private function checkForBlockingVerifyErrors(array $errors, array $nonBlockingErrors): void
    {
        foreach ($errors as $key => $erreur) {
            if (!in_array($erreur["errorCode"], $nonBlockingErrors)) {
                throw new Exception($erreur["message"]);
            }
        }
    }
}