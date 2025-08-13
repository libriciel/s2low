<?php

namespace S2lowLegacy\Class;

use DateTime;
use Exception;
use S2lowLegacy\Lib\PemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;

class VerifyPadesSignature
{
    /** @var VerifyPemCertificate  */
    private $verifyPemCertificate;
    /** @var PemCertificateFactory  */
    private $pemCertificateFactory;

    public function __construct(
        $rgs_validca_path,
        VerifyPemCertificateFactory $verifyPemCertificateFactory,
        PemCertificateFactory $pemCertificateFactory
    ) {
        $this->verifyPemCertificate = $verifyPemCertificateFactory->get($rgs_validca_path);
        $this->pemCertificateFactory = $pemCertificateFactory;
    }

    /**
     * @param $signature
     * @throws Exception
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
    public function validateSignature($signature, bool $mustCheckInCertificateStore = true): void
    {
        $pemCertificate = $this->validateSignatureWithoutCertificateChecking($signature);
        if (!$mustCheckInCertificateStore) {
            // TODO : supprimer
            // la fonction validateCertificateFomSignature laisse déjà passer les cas ou on n'est pas capable de
            // construire une chaine de certification dans le magasin de certificat.
            // Il n'est donc pas nécessaire de distinguer ce cas.
            // on laisse ça là au cas ou on a cassé un autre cas et qu'il faudrait refaire un patch rapidement.
        }
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
