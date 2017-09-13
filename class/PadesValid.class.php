<?php

class PadesValid {

    private $pades_valid_url;
    private $rgs_validca_path;

    /** @var  CurlWrapper */
    private $curlWrapper;

    /** @var  VerifyPKCS7SIgnature */
    private $verifyPKCS7SIgnature;

    private $last_result;

    public function __construct($pades_valid_url, $rgs_validca_path) {
        $this->pades_valid_url = $pades_valid_url;
        $this->rgs_validca_path = $rgs_validca_path;
        $this->setCurlWrapper(new CurlWrapper());
        $this->setVerifyPKCS7Signature(new VerifyPKCS7SIgnature($this->rgs_validca_path));
    }
    public function setCurlWrapper(CurlWrapper $curlWrapper){
        $this->curlWrapper = $curlWrapper;
    }

    public function setVerifyPKCS7Signature(VerifyPKCS7SIgnature $verifyPKCS7SIgnature){
        $this->verifyPKCS7SIgnature = $verifyPKCS7SIgnature;
    }

    public function getLastResult(){
        return $this->last_result;
    }

    public function validate($filepath){
        $this->curlWrapper->addPostFile('file',$filepath);
        $result = $this->curlWrapper->get($this->pades_valid_url);
        $this->last_result = $result;
        if (!$result){
            throw new Exception($this->curlWrapper->getLastError()." ".$this->curlWrapper->getLastOutput());
        }
        $result = json_decode($result);
        if (! $result){
            throw new Exception("Impossible de décoder le message de pades-valid : ".$this->curlWrapper->getLastOutput());
        }
        if (! isset($result->signed)){
            throw new Exception("Impossible de determiner si le fichier est signé");
        }

        if ($result->signed == false){
            //Le fichier n'est pas signée
            return false;
        }
        if (empty($result->signatures)){
            throw new Exception("Impossible de determiner si le fichier est signé");
        }
        foreach($result->signatures as $signature){
            $this->validSignature($signature);
        }
        return true;
    }

    private function validSignature($signature){
        if (empty($signature->valid) || ! $signature->valid){
            throw new Exception("Au moins une signature n'est pas valide");
        }
        if(empty($signature->signingCert)){
            throw new Exception("Impossible de récupérer le certificat de signature");
        };
        if (empty($signature->signatureDate)){
            throw new Exception("Impossible de determiner la date de la signature");
        }

        $beginpem = "-----BEGIN CERTIFICATE-----\n";
        $endpem = "\n-----END CERTIFICATE-----\n";
        $signing_cert = $beginpem.$signature->signingCert.$endpem;
        $x509_info = openssl_x509_parse($signing_cert);

        $signatureDate = floor($signature->signatureDate / 1000);

        if ($signatureDate < $x509_info['validFrom_time_t'] ||
            $signatureDate > $x509_info['validTo_time_t']
        ) {
            throw new Exception("La date de la signature {$signature->signatureDate}" .
                " n'entre pas dans la date de validité du certitficat {$x509_info['validFrom_time_t']} - {$x509_info['validTo_time_t']}");
        }

        $certificate_path = sys_get_temp_dir()."/s2low_valid_certifcate_".time().mt_rand(0,mt_getrandmax());
        file_put_contents($certificate_path,$signing_cert);

        try {
            $this->verifyPKCS7SIgnature->checkCertificate($certificate_path);
        } catch (Exception $e){
            unlink($certificate_path);
            throw $e;
        }
        unlink($certificate_path);
        return true;
    }

}