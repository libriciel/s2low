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
    public function addBeginAndEndToPemCertificate($nakedCertificate){
        $beginpem = "-----BEGIN CERTIFICATE-----\n";
        $endpem = "\n-----END CERTIFICATE-----\n";

        $signing_cert = implode("\n",str_split($nakedCertificate,78));
        return $beginpem.$signing_cert.$endpem;
    }

    public function parsePemCertificate($certificate){
        $x509_info = openssl_x509_parse($certificate);

        if(!$x509_info){
            throw new Exception("Problème à l'ouverture du certificat : ".openssl_error_string());
        }
        return $x509_info;
    }

    /**
     * @param $certificate_path
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

    public function checkCertificateIsValidAtDate( $date, $dateValidFrom, $dateValidTo){
        if ($date < $dateValidFrom || $date > $dateValidTo) {
            throw new Exception("La date de la signature ".$date->format("d-M-Y H:i:s") .
                        " n'entre pas dans la date de validité du certificat ".
                        $dateValidFrom->format("d-M-Y H:i:s")." - ".$dateValidTo->format("d-M-Y H:i:s"));
        }
    }

    public function checkCertificateWithoutCheckingCertificateChain($certificate_path, string $timestamp =null){
        return $this->checkCertificate($certificate_path, $timestamp,$this::CERTIFICATE_CHAIN_ERRORS);
    }

    public function checkCertificate($certificate_path, $timestamp = null, $filteredErrors = []): bool
    {
        $erreursVerifyOpenSsl =  $this->launchOpenSslVerify($certificate_path,$timestamp);

        $this->checkForBlockingVerifyErrors($erreursVerifyOpenSsl, $filteredErrors);

        $x509_info = $this->parsePemCertificate(file_get_contents($certificate_path));

        $dateSignature = new DateTime("NOW");

        if(!is_null($timestamp)){
            $dateSignature->setTimestamp($timestamp);
        }

        $this->checkCertificateIsValidAtDate(
            $dateSignature,
            new DateTime(date(DATE_RFC2822, $x509_info['validFrom_time_t'])),
            new DateTime(date(DATE_RFC2822, $x509_info['validTo_time_t'])));

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