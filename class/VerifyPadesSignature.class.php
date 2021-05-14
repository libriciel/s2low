<?php


class VerifyPadesSignature
{
    /** @var VerifyPemCertificate  */
    private $verifyPemCertificate;

    public function __construct(
        $rgs_validca_path,
        VerifyPemCertificateFactory $verifyPemCertificateFactory)
    {
        $this->verifyPemCertificate = $verifyPemCertificateFactory->get($rgs_validca_path);
    }

    /**
     * @param $signature
     * @throws Exception
     */
    public function validateSignatureWithoutCertificateChecking($signature): void
    {
        $this->checkNecessaryFields($signature);
        $signature->pemCertificate = $this->verifyPemCertificate->addBeginAndEndToPemCertificate($signature->signingCert);
        $signature->x509_info = $this->verifyPemCertificate->parsePemCertificate($signature->pemCertificate);
        $this->verifyPemCertificate->checkCertificateIsValidAtDate(
            $this->getTimestampFromSignature($signature),
            $signature->x509_info['validFrom_time_t'],
            $signature->x509_info['validTo_time_t']
        );
    }

    /**
     * @param $signature
     * @throws Exception
     */
    public function validateSignature($signature): void
    {
        $this->checkNecessaryFields($signature);
        $signature->pemCertificate = $this->verifyPemCertificate->addBeginAndEndToPemCertificate($signature->signingCert);
        $signature->x509_info = $this->verifyPemCertificate->parsePemCertificate($signature->pemCertificate);
        $this->verifyPemCertificate->checkCertificateIsValidAtDate(
            $this->getTimestampFromSignature($signature),
            $signature->x509_info['validFrom_time_t'],
            $signature->x509_info['validTo_time_t']
        );
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
            $this->verifyPemCertificate->checkCertificate(
            $certificate_path,
            VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
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
     * @return false|float
     */
    private function getTimestampFromSignature($signature)
    {
        return floor($signature->signatureDate / 1000);
    }
}