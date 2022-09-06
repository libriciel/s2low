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

    public function __construct(string $content, array $x509)
    {
        $this->content = $content;
        $this->dateValidFrom = new DateTime();
        $this->dateValidFrom->setTimestamp($x509['validFrom_time_t']);
        $this->dateValidTo = new DateTime();
        $this->dateValidTo->setTimestamp($x509['validTo_time_t']);
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

    public function getDer()
    {
        return base64_decode($this->getContentStrippedFromBegin());
    }

    public function getHash()
    {
        return base64_encode(
            openssl_digest($this->getDer(), UserSQL::CERTIFICATE_FINGERPRINT_HASH_ALG, true)
        );
    }

    public function getContentStrippedFromBegin()
    {
        $begin = "CERTIFICATE-----";
        $end = "-----END";
        $pem_data = mb_substr($this->content, mb_strpos($this->content, $begin) + mb_strlen($begin));
        $pem_data = trim(mb_substr($pem_data, 0, mb_strpos($pem_data, $end)));
        return $pem_data;
    }
}
