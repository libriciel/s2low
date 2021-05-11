<?php

class VerifyPemCertificateFactory{
    public function get(string $caCertificatesPath) : VerifyPemCertificate
    {
        return new VerifyPemCertificate($caCertificatesPath);
    }
}