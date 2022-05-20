<?php

class VerifyPemCertificateFactory{
    public function get(string $caCertificatesPath) : VerifyPemCertificate
    {
        return new VerifyPemCertificate(
            $caCertificatesPath,
            new \S2low\Services\ProcessCommand\ExtractIssuerHashCommand(),
            new \S2low\Services\ProcessCommand\ExtractCertificateSNCommand(),
            new \S2low\Services\ProcessCommand\CheckSnInCRLCommand(),
            new \S2low\Services\ProcessCommand\OpensslVerifyCommand($caCertificatesPath)
        );
    }
}