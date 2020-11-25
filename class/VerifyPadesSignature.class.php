<?php


class VerifyPadesSignature
{
    private $authorized_ca_path;
    /** @var VerifyPKCS7Signature  */
    private $tempVerifyPKCS7Signature;
    /** @var VerifyPemCertificate  */
    private $verifyPemCertificate;

    public function __construct($authorized_ca_path){
        $this->authorized_ca_path = $authorized_ca_path;
        $this->tempVerifyPKCS7Signature = new VerifyPKCS7Signature($authorized_ca_path);    //TODO : remove
        $this->verifyPemCertificate = new VerifyPemCertificate($authorized_ca_path);        //TODO : use injection
    }

    public function setTempVerifyPKCS7Signature(VerifyPKCS7Signature $verifyPKCS7Signature){    //TODO : remove
        $this->tempVerifyPKCS7Signature = $verifyPKCS7Signature;
    }

    /**
     * @param $signature
     * @throws Exception
     */
    public function validateSignatureWithoutCertificateChecking($signature): void
    {
        $signature->pemCertificate = $this->verifyPemCertificate->addBeginAndEndToPemCertificate($signature->signingCert);
        $this->checkNecessaryFields($signature);
        $signature->x509_info = $this->verifyPemCertificate->parsePemCertificate($signature->pemCertificate);
        $this->checkCertificateWasValidAtSignatureTime($signature->x509_info, $signature);
    }

    /**
     * @param $signature
     * @throws Exception
     */
    public function validateSignature($signature): void
    {
        $this->validateSignatureWithoutCertificateChecking($signature);
        $this->validateCertificateFomSignature(
            $signature->pemCertificate,
            $this->getTimestampFromSignature($signature)
        );
    }

    /**
     * @param $signature
     * @return bool
     * @throws Exception
     */
    private function validateCertificateFomSignature($certificateContent,$signatureTimestamp){
        $certificate_path = sys_get_temp_dir()."/s2low_valid_certifcate_".time().mt_rand(0,mt_getrandmax());
        file_put_contents($certificate_path,$certificateContent);
        try {
            $this->tempVerifyPKCS7Signature->checkCertificateWithoutCheckingCertificateChain(
                $certificate_path,
                $signatureTimestamp
            );
        } catch (Exception $e){
            unlink($certificate_path);
            throw $e;
        }
        unlink($certificate_path);
        return true;
    }

    private function checkNecessaryFields($signature){
        if (empty($signature->valid) || ! $signature->valid){
            throw new Exception("Au moins une signature n'est pas valide");
        }
        if(empty($signature->signingCert)){
            throw new Exception("Impossible de récupérer le certificat de signature");
        };
        if (empty($signature->signatureDate)){
            throw new Exception("Impossible de determiner la date de la signature");
        }
    }

    /**
     * @param $signature
     * @return void
     * @throws Exception
     */
    private function checkCertificateWasValidAtSignatureTime($x509_info, $signature){
        $signatureDate = $this->getTimestampFromSignature($signature);

        if ($signatureDate < $x509_info['validFrom_time_t'] ||
            $signatureDate > $x509_info['validTo_time_t']
        ) {
            throw new Exception("La date de la signature {$signature->signatureDate}" .
                " n'entre pas dans la date de validité du certitficat {$x509_info['validFrom_time_t']} - {$x509_info['validTo_time_t']}");
        }
    }

    /**
     * @param $signature
     * @return false|float
     */
    private function getTimestampFromSignature($signature)
    {
        return floor($signature->signatureDate / 1000);
    }
}