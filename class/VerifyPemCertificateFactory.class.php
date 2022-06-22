<?php

use S2low\Services\ProcessCommand\CommandLauncher;

class VerifyPemCertificateFactory
{
    public function get(string $caCertificatesPath): VerifyPemCertificate
    {
        return new VerifyPemCertificate(
            $caCertificatesPath,
            new \S2low\Services\ProcessCommand\OpenSSLWrapper($caCertificatesPath, new CommandLauncher())
        );
    }
}
