<?php

class PemCertificate{
    /** @var string */
    private $content;
    /** @var array  */
    private $x509;

    public function __construct(string $content,array $x509)
    {
        $this->content = $content;
        $this->x509 = $x509;
    }

    public function getContent() : string
    {
        return $this->content;
    }

    public function checkCertificateIsValidAtDate( $date)
    {
        if ($date < $this->x509['validFrom_time_t'] || $date > $this->x509['validTo_time_t']) {
            throw new Exception("La date de la signature ".$date->format("d-M-Y H:i:s") .
                " n'entre pas dans la date de validité du certificat ".
                /*$dateValidFrom->format("d-M-Y H:i:s").*/" - "/*.$dateValidTo->format("d-M-Y H:i:s")*/);
        }
    }
}