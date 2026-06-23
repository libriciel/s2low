<?php

namespace S2lowLegacy\Class;

use DateTime;
use Exception;
use S2lowLegacy\Lib\PemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;

class VerifyPadesSignature
{
    public function __construct(
        private readonly string $rgs_validca_path,
        private readonly VerifyPemCertificate $verifyPemCertificate,
        private readonly PemCertificateFactory $pemCertificateFactory
    ) {
    }

    /**
     * @param $signature
     * @return \S2lowLegacy\Lib\PemCertificate
     * @throws \Exception
     */
    public function validateSignatureWithoutCertificateChecking($signature): PemCertificate
    {
        $this->checkNecessaryFields($signature);
        $pemCertificate = $this->pemCertificateFactory
            ->getFromMinimalString($signature->signingCert);
        $pemCertificate->checkCertificateIsValidAtDate(
            $this->getDateTimeFromSignature($signature)
        );
        return $pemCertificate;
    }

    /**
     * @param $signature
     * @throws Exception
     */
    public function validateSignature($signature): void
    {
        $pemCertificate = $this->validateSignatureWithoutCertificateChecking($signature);
        $this->validateCertificateFomSignature(
            $pemCertificate->getContent(),
            $this->getTimestampFromSignature($signature)
        );
    }

    /**
     * @param $signature
     * @return bool
     * @throws Exception
     */
    private function validateCertificateFomSignature($certificateContent, $signatureTimestamp)
    {
        $certificate_path = sys_get_temp_dir() . "/s2low_valid_certifcate_" . time() . mt_rand(0, mt_getrandmax());
        file_put_contents($certificate_path, $certificateContent);
        try {
            $this->verifyPemCertificate->checkCertificateWithOpenSSL(
                $certificate_path,
                $this->rgs_validca_path,
                VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS,
                $signatureTimestamp
            );
        } catch (Exception $e) {
            unlink($certificate_path);
            throw $e;
        }
        unlink($certificate_path);
        return true;
    }

    private function checkNecessaryFields($signature)
    {
        if (empty($signature->valid) || ! $signature->valid) {
            throw new Exception("Au moins une signature n'est pas valide");
        }
        if (empty($signature->signingCert)) {
            throw new Exception("Impossible de récupérer le certificat de signature");
        };
        if (empty($signature->signatureDate)) {
            throw new Exception("Impossible de determiner la date de la signature");
        }
    }

    /**
     * @param $signature
     * @return \DateTime
     */
    private function getDateTimeFromSignature($signature): DateTime
    {
        $date = new DateTime();
        $date->setTimestamp($this->getTimestampFromSignature($signature));
        return $date;
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
