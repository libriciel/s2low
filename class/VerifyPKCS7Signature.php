<?php

namespace S2lowLegacy\Class;

use DateTime;
use Exception;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2low\Services\ProcessCommand\OpenSSLWrapper;

class VerifyPKCS7Signature
{
    private OpenSSLWrapper $openSSLWrapper;


    public function __construct(
        string $authorized_ca_path,
        OpenSSLWrapper $openSSLWrapper,
        private readonly VerifyPemCertificate $verifyPemCertificate,
        private readonly PemCertificateFactory $pemCertificateFactory
    ) {
        $this->openSSLWrapper = $openSSLWrapper;
        $this->authorized_ca_path = $authorized_ca_path;
    }

    /**
     * @param string $signature
     * @param array $filteredErrors
     * @param string|null $file_path
     * @param \DateTime|null $dateTime
     * @return bool
     * @throws Exception
     */

    public function verifySignature(
        string $signature,
        array $filteredErrors = [],
        ?string $file_path = null,
        ?DateTime $dateTime = null,
    ): bool {
        if (is_null($dateTime)) {
            $dateTime = new DateTime();
        }
        list($signature_file, $certificate_file, $certificate) = $this->extractDataFromPKCS7Signature($signature);
        try {
            $pemCertificate = $this->pemCertificateFactory->getFromString($certificate);
            $pemCertificate->checkCertificateIsValidAtDate($dateTime);

            $this->verifyPemCertificate->checkCertificateWithOpenSSL(
                $certificate_file,
                $this->authorized_ca_path,
                $filteredErrors,
                $dateTime->getTimestamp()
            );
            if ($file_path) {
                $this->openSSLWrapper->checkFileContentCorrespondsToSignature(
                    $signature_file,
                    $this->authorized_ca_path,
                    $file_path
                );
            }
        } finally {
            unlink($signature_file);
            unlink($certificate_file);
        }
        return true;
    }


    /**
     * @param string $signatureFileName
     * @return string[]
     * @throws \Exception
     */
    private function extractDataFromPKCS7Signature(string $signatureFileName): array
    {
        $signature_file = $this->createTempFile($signatureFileName, "slow_signature");

        $certificate = $this->openSSLWrapper->getCertificateFromPKCS7Signature($signature_file);

        try {
            $certificate_file = $this->createTempFile($certificate, "slow_certificate");
        } catch (Exception $e) {
            unlink($signature_file);
            throw $e;
        }

        return array($signature_file, $certificate_file, $certificate);
    }

    /**
     * @param $signature
     * @param $name
     * @return string
     * @throws Exception
     */
    private function createTempFile($signature, $name): string
    {
        $signature_file = sys_get_temp_dir() . "/" . $name . "_" . mt_rand(0, mt_getrandmax());

        $result = file_put_contents($signature_file, $signature);
        if ($result === false) {
            throw new Exception("Impossible d'écrire la signature dans $signature_file");
        }
        return $signature_file;
    }
}
