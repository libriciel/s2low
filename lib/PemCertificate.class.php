<?php

class PemCertificate{
    /** @var string */
    private $content;
    /** @var array  */
    private $x509;
    /** @var \DateTime  */
    private $dateValidFrom;
    /** @var \DateTime  */
    private $dateValidTo;

    public function __construct(string $content,array $x509)
    {
        $this->content = $content;
        $this->x509 = $x509;
        $this->dateValidFrom = new DateTime();
        $this->dateValidFrom->setTimestamp($x509['validFrom_time_t']);
        $this->dateValidTo = new DateTime();
        $this->dateValidTo->setTimestamp($x509['validTo_time_t']);
    }

    public function getContent() : string
    {
        return $this->content;
    }

    public function checkCertificateIsValidAtDate(DateTime $date)
    {
        if ($date < $this->dateValidFrom || $date > $this->dateValidTo) {
            throw new Exception("La date de la signature ".$date->format("d-M-Y H:i:s") .
                " n'entre pas dans la date de validité du certificat ".
                    $this->dateValidFrom->format("d-M-Y H:i:s")." - ".$this->dateValidTo->format("d-M-Y H:i:s"));
        }
    }
}