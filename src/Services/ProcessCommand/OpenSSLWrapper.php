<?php

namespace S2low\Services\ProcessCommand;

class OpenSSLWrapper
{
    /**
     * @var string
     */
    private $authorized_ca_path;
    /**
     * @var \S2low\Services\ProcessCommand\CommandLauncher
     */
    private $commandLauncher;

    public function __construct(string $authorized_ca_path, CommandLauncher $commandLauncher)
    {
        $this->commandLauncher = $commandLauncher;
        $this->authorized_ca_path = $authorized_ca_path;
    }

    /**
     * @throws \RecoverableException
     */
    public function verify(string $certificate_path, array $nonBlockingErrors, string $timestamp = null): void
    {
        $verifyCmd = ["openssl","verify","-CApath", $this->authorized_ca_path, $certificate_path];

        if ($timestamp) {
            $verifyCmd = ["openssl","verify","-CApath",$this->authorized_ca_path,"-attime",$timestamp, $certificate_path];
        }

        $this->commandLauncher->launch(
            $verifyCmd,
            new OpensslVerifyCommandOutputTranslator($nonBlockingErrors)
        );
    }

    /**
     * @throws \RecoverableException
     */
    public function extractCertificateSN(string $path): string
    {
        return $this->commandLauncher->launch(
            ["openssl","x509","-noout","-serial","-in",$path],
            new ExtractCertificateSNCommandOutputTranslator()
        );
    }

    public function extractHash(string $path): string
    {
        return $this->commandLauncher->launch(
            [OPENSSL_PATH,"x509","-noout","-issuer_hash","-in", "$path"],
            new ExtractIssuerHashCommandOutputTranslator()
        );
    }

    public function checkSNIsInCRL(string $crlPath, string $serialNumber): void
    {
        $this->commandLauncher->launch(
            ["openssl","crl","-in",$crlPath,"-text","-noout"],
            new CheckSnInCRLCommandOutputTranslator($serialNumber)
        );
    }
}
