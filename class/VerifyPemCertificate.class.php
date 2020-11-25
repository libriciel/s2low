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
     * @param $out
     * @param $ret
     * @param $matches
     * @return array
     * @throws Exception
     */
    public function analyseCertificate($certificate_path, string $timestamp =null): array
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

        $certificateChainErrorThrown = false;

        foreach ($erreurs as $erreur){
            if(!in_array($erreur["errorCode"],$this::CERTIFICATE_CHAIN_ERRORS)){
                $certificateChainErrorThrown = true;
            }
        }

        if (!$certificateChainErrorThrown) {
            // La date n'est alors pas forcément vérifiée par openssl si une erreur liée à la chaine de certificats
            //  a été détectée.
            // Copié - collé depuis PadesValid => REFACTO NECESSAIRE
            $x509_info = openssl_x509_parse(file_get_contents($certificate_path));  #Moche

            $dateValidFrom = new DateTime(date(DATE_RFC2822, $x509_info['validFrom_time_t']));
            $dateValidTo = new DateTime(date(DATE_RFC2822, $x509_info['validTo_time_t']));
            $dateSignature = new DateTime("NOW");

            if(!is_null($timestamp)){
                $dateSignature->setTimestamp($timestamp);
            }

            if ($dateSignature < $dateValidFrom || $dateSignature > $dateValidTo) {
                $erreurs[] = [
                    "errorCode"=>10,
                    "depth"=>0,
                    "message"=>"La date de la signature ".$dateSignature->format("d-M-Y H:i:s") .
                        " n'entre pas dans la date de validité du certificat ".
                        $dateValidFrom->format("d-M-Y H:i:s")." - ".$dateValidTo->format("d-M-Y H:i:s")
                ];
            }
        }

        return $erreurs;
    }

    public function checkCertificateWithoutCheckingCertificateChain($certificate_path, string $timestamp =null){
        $erreurs =  $this->analyseCertificate($certificate_path,$timestamp);

        foreach ($erreurs as $key=>$erreur){
            if(in_array($erreur["errorCode"],$this::CERTIFICATE_CHAIN_ERRORS)){
                unset($erreurs[$key]);
            }
        }
        $erreursRearrangees=array_values($erreurs);
        if(!empty($erreursRearrangees)){
            throw new Exception($erreursRearrangees[0]["message"]);
        }
        return true;
    }

    public function checkCertificate($certificate_path) {
        $erreurs =  $this->analyseCertificate($certificate_path);
        if(!empty($erreurs)){
            throw new Exception($erreurs[0]["message"]);
        }
        return true;
    }
}