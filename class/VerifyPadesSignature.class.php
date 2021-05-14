<?php


class VerifyPadesSignature
{
    /** @var VerifyPemCertificate  */
    private $verifyPemCertificate;
    /** @var \PemCertificateFactory  */
    private $pemCertificateFactory;

    public function __construct(
        $rgs_validca_path,
        VerifyPemCertificateFactory $verifyPemCertificateFactory,
        PemCertificateFactory $pemCertificateFactory
    )
    {
        $this->verifyPemCertificate = $verifyPemCertificateFactory->get($rgs_validca_path);
        $this->pemCertificateFactory = $pemCertificateFactory;
    }

    /**
     * @param $signature
     * @throws Exception
     */
    public function validateSignatureWithoutCertificateChecking($signature): void
    {
        $this->checkNecessaryFields($signature);
        $this->pemCertificateFactory
            ->getFromMinimalString($signature->signingCert)
            ->checkCertificateIsValidAtDate(
                $this->getTimestampFromSignature($signature)
        );
    }

    /**
     * @param $signature
     * @throws Exception
     */
    public function validateSignature($signature): void
    {
        $this->checkNecessaryFields($signature);
        $certificate = $this->pemCertificateFactory->getFromMinimalString($signature->signingCert);
        $certificate->checkCertificateIsValidAtDate(
            $this->getTimestampFromSignature($signature)
        );
        $this->validateCertificateFomSignature(
            $certificate->getContent(),
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
            $this->verifyPemCertificate->checkCertificateWithOpenSSL(
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