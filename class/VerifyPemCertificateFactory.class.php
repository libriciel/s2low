<?php

class VerifyPemCertificateFactory{
    public function get(string $caCertificatesPath) : VerifyPemCertificate
    {
        return new VerifyPemCertificate(
            $caCertificatesPath,
            new \S2low\Services\ExtractIssuerHashCommand(),
            new \S2low\Services\ExtractCertificateSNCommand(),
            new \S2low\Services\CheckSnInCRLCommand(),
            new \S2low\Services\OpensslVerifyCommand($caCertificatesPath)
        );
    }
}