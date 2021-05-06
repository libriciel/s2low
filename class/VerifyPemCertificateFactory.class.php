<?php

class VerifyPemCertificateFactory{
    public function get(string $caCertificatesPath){
        return new VerifyPemCertificate($caCertificatesPath);
    }
}