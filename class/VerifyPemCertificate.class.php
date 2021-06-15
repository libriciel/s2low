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
    /** @var string  */
    private $authorized_ca_path;

    public function __construct(string $authorized_ca_path){
        $this->authorized_ca_path = $authorized_ca_path;
    }

    /**
     * @throws Exception
     */
    public function checkCertificateWithOpenSSL($certificate_path, array $filteredErrors = [], string $timestamp = null ): bool
    {
        $this->checkForCrlRevocation($certificate_path);
        $erreursVerifyOpenSsl =  $this->launchOpenSslVerify($certificate_path,$timestamp);
        $this->checkForBlockingVerifyErrors($erreursVerifyOpenSsl, $filteredErrors);

        return true;
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
     * @param array $errors
     * @param array $nonBlockingErrors
     * @throws Exception
     */
    private function checkForBlockingVerifyErrors(array $errors, array $nonBlockingErrors): void
    {
        foreach ($errors as $erreur) {
            if (!in_array($erreur["errorCode"], $nonBlockingErrors)) {
                throw new Exception($erreur["message"]);
            }
        }
    }

    /**
     * @param string $file
     * @return void
     * @throws \Exception
     */
    protected function checkForCrlRevocation(string $file) : void
    {
        $commandShowHash = OPENSSL_PATH . " x509 -issuer_hash -noout -in $file 2>/dev/null";
        exec($commandShowHash, $outputShowHash, $return_var);

        if ($return_var != 0) {
            throw new Exception("Certificat non valide : impossible d'extraire le issuer hash");
        }

        $file_r0 = $this->authorized_ca_path . "/{$outputShowHash[0]}.r0";

        if (file_exists($file_r0)) {
            // 1) extraire le SN du certificat
            // openssl x509 -noout -serial -in cert. pem
            $commandGetSerialNumber = "openssl x509 -noout -serial -in $file";
            exec($commandGetSerialNumber, $outputGetSerialNumber, $return_var);
            if ($return_var != 0 || !preg_match("#serial=(.*)#",$outputGetSerialNumber[0],$serialNumberMatches)) {
                throw new Exception("Impossible d'extraire le SN du certificat");
            }
            $serialNumber = $serialNumberMatches[1];

            // 2) vérifier que ce SN n'est pas présent dans la CRL (Pour l'instant, la date n'est pas prise en compte)
            $commandCheckSnInCRL = "openssl crl -in $file_r0 -text -noout | grep $serialNumber";
            // On ne vérifie pas
            // 1) la date
            // 2) si la CRL garde bien les certificats expirés ( extension 2.5.29.60 )
            exec($commandCheckSnInCRL, $output3, $return_var);
            if (!$return_var) {
                throw new Exception("Certificat révoqué");
            }
        }
    }
}