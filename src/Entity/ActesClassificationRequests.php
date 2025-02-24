<?php

namespace S2low\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ActesClassificationRequests
 *
 * @ORM\Table(name="actes_classification_requests")
 * @ORM\Entity
 */
class ActesClassificationRequests
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_classification_requests_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="request_date", type="datetimetz", nullable=true)
     */
    private $requestDate;

    /**
     * @var int|null
     *
     * @ORM\Column(name="requested_by", type="integer", nullable=true)
     */
    private $requestedBy;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="version_date", type="datetimetz", nullable=true)
     */
    private $versionDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="xml_data", type="blob", nullable=true)
     */
    private $xmlData;

    /**
     * @var string|null
     *
     * @ORM\Column(name="xml_data_texte", type="text", nullable=true)
     */
    private $xmlDataTexte;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestDate(): ?\DateTimeInterface
    {
        return $this->requestDate;
    }

    public function setRequestDate(?\DateTimeInterface $requestDate): static
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    public function getRequestedBy(): ?int
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?int $requestedBy): static
    {
        $this->requestedBy = $requestedBy;

        return $this;
    }

    public function getVersionDate(): ?\DateTimeInterface
    {
        return $this->versionDate;
    }

    public function setVersionDate(?\DateTimeInterface $versionDate): static
    {
        $this->versionDate = $versionDate;

        return $this;
    }

    public function getXmlData()
    {
        return $this->xmlData;
    }

    public function setXmlData($xmlData): static
    {
        $this->xmlData = $xmlData;

        return $this;
    }

    public function getXmlDataTexte(): ?string
    {
        return $this->xmlDataTexte;
    }

    public function setXmlDataTexte(?string $xmlDataTexte): static
    {
        $this->xmlDataTexte = $xmlDataTexte;

        return $this;
    }


}
