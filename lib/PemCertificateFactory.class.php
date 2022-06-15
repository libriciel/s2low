<?php


class PemCertificateFactory
{
    private function addBeginAndEndToPemCertificate(string $nakedCertificate) : string
    {
        $beginpem = "-----BEGIN CERTIFICATE-----\n";
        $endpem = "\n-----END CERTIFICATE-----\n";

        $nakedCertificate = trim($nakedCertificate);

        if (strlen(explode("\n",$nakedCertificate)[0]) >= 64) {
            $nakedCertificate = preg_replace('/\s+/', ' ', trim($nakedCertificate));
            $nakedCertificate = rtrim(chunk_split($nakedCertificate, 64, "\n"));
        }
        return $beginpem . $nakedCertificate . $endpem;
    }

    /**
     * @throws Exception
     */
    private function parsePemCertificate(string $certificate): array
    {
        $x509_info = openssl_x509_parse($certificate);

        if(!$x509_info){
            throw new Exception("Problème à l'ouverture du certificat : ".openssl_error_string());
        }
        return $x509_info;
    }

    /**
     * @throws Exception
     */
    public function getFromString(string $content): PemCertificate
    {
        return new PemCertificate(
            $content,
            $this->parsePemCertificate($content)
        );
    }

    /**
     * @throws Exception
     */
    public function getFromMinimalString(string $minimalContent): PemCertificate
    {
        return $this->getFromString(
            $this->addBeginAndEndToPemCertificate($minimalContent)
        );
    }

}