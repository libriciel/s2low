<?php

namespace S2low\Services\ProcessCommand;

use DateTime;
use S2lowLegacy\Class\RecoverableException;
use Exception;

class OpenSSLWrapper
{
    /**
     * @var \S2low\Services\ProcessCommand\CommandLauncher
     */
    private $commandLauncher;

    public function __construct(
        CommandLauncher $commandLauncher,
        private readonly CheckSnInCRLFactory $checkSnInCRLFactory
    ) {
        $this->commandLauncher = $commandLauncher;
    }

    /**
     * @throws RecoverableException
     */
    public function verify(string $certificate_path, string $ca_path, array $nonBlockingErrors, ?string $timestamp = null): void
    {
        $verifyCmd = ["openssl","verify","-CApath", $ca_path, $certificate_path];

        if ($timestamp) {
            $verifyCmd = ["openssl","verify","-CApath",$ca_path,"-attime",$timestamp, $certificate_path];
        }

        $this->commandLauncher->launch(
            $verifyCmd,
            new OpensslVerifyCommandOutputTranslator($nonBlockingErrors)
        );
    }

    /**
     * @throws RecoverableException
     */
    public function extractCertificateSN(string $path): string
    {
        return $this->commandLauncher->launch(
            ["openssl","x509","-noout","-serial","-in",$path],
            new ExtractCertificateSNCommandOutputTranslator()
        );
    }

    /**
     * @throws RecoverableException
     */
    public function extractHash(string $path): string
    {
        return $this->commandLauncher->launch(
            [OPENSSL_PATH,"x509","-noout","-issuer_hash","-in", "$path"],
            new ExtractIssuerHashCommandOutputTranslator()
        );
    }

    /**
     * @throws RecoverableException
     */
    public function checkSNIsInCRL(string $crlPath, string $serialNumber, DateTime $dateTime): void
    {
        $this->commandLauncher->launch(
            ["openssl","crl","-in",$crlPath,"-text","-noout"],
            $this->checkSnInCRLFactory->get($serialNumber, $dateTime)
        );
    }

    /**
     * @param string $signatureFileName
     * @return string
     * @throws Exception
     */
    public function getCertificateFromPKCS7Signature(string $signatureFileName): string
    {
        return $this->commandLauncher->launchFromString(
            "openssl pkcs7 -in $signatureFileName -print_certs | openssl x509",
            new CertificateFromPKCS7CommandOutputTranslator()
        );
    }

    /**
     * @param $signature_file
     * @param $file_path
     * @return void
     * @throws Exception
     */
    public function checkFileContentCorrespondsToSignature($signature_file, $file_path, string $ca_path): string
    {
        # On ne va pas vérifier le certificat (option -noverify)
        # Au niveau du purpose, smime est trop restrictif par rapport à notre besoin
        # Au niveau de la date et de la chaine de certification, on va se reposer sur
        # la fonction précédente
        return $this->commandLauncher->launchFromString(
            "openssl smime -in $signature_file -inform PEM -verify -noverify -content $file_path -CApath {$ca_path}",
            new FileContentCorrespondsToSignatureChecker()
        );
    }
}
