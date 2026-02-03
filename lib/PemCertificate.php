<?php

namespace S2lowLegacy\Lib;

use DateTime;
use Exception;
use S2lowLegacy\Model\UserSQL;

class PemCertificate
{
    /** @var string */
    private $content;
    /** @var DateTime  */
    private $dateValidFrom;
    /** @var DateTime  */
    private $dateValidTo;
    private array $subjectDN;
    private array $issuerDN;

    public function __construct(string $content, array $x509, array $subjectDN, array $issuerDN)
    {
        $this->content = $content;
        $this->dateValidFrom = new DateTime();
        $this->dateValidFrom->setTimestamp($x509['validFrom_time_t']);
        $this->dateValidTo = new DateTime();
        $this->dateValidTo->setTimestamp($x509['validTo_time_t']);
        $this->subjectDN = $subjectDN;
        $this->issuerDN = $issuerDN;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @throws \Exception
     */
    public function checkCertificateIsValidAtDate(DateTime $date)
    {
        if ($date < $this->dateValidFrom || $date > $this->dateValidTo) {
            throw new Exception("La date de la signature " . $date->format("d-M-Y H:i:s") .
                " n'entre pas dans la date de validité du certificat " .
                    $this->dateValidFrom->format("d-M-Y H:i:s") . " - " . $this->dateValidTo->format("d-M-Y H:i:s"));
        }
    }

    public function getSubjectDN(): array
    {
        return $this->subjectDN;
    }

    public function getIssuerDN(): array
    {
        return $this->issuerDN;
    }

    public function isAutosigned(): bool
    {
        return $this->subjectDN === $this->issuerDN;
    }

    /**
     * @throws Exception
     */
    public function checkValidity()
    {
        $this->checkCertificateIsValidAtDate(new DateTime());
    }

    public function isIssuedBy(PemCertificate $childCertificate): bool
    {
        return $this->getSubjectDN() === $childCertificate->getIssuerDN();
    }
}
