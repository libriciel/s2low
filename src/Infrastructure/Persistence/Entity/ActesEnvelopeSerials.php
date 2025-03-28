<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ActesEnvelopeSerials
 *
 * @ORM\Table(name="actes_envelope_serials")
 * @ORM\Entity
 */
class ActesEnvelopeSerials
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_envelope_serials_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=true)
     */
    private $authorityId;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="reset_date", type="datetimetz", nullable=true)
     */
    private $resetDate;

    /**
     * @var int|null
     *
     * @ORM\Column(name="serial", type="integer", nullable=true)
     */
    private $serial;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthorityId(): ?int
    {
        return $this->authorityId;
    }

    public function setAuthorityId(?int $authorityId): static
    {
        $this->authorityId = $authorityId;

        return $this;
    }

    public function getResetDate(): ?\DateTimeInterface
    {
        return $this->resetDate;
    }

    public function setResetDate(?\DateTimeInterface $resetDate): static
    {
        $this->resetDate = $resetDate;

        return $this;
    }

    public function getSerial(): ?int
    {
        return $this->serial;
    }

    public function setSerial(?int $serial): static
    {
        $this->serial = $serial;

        return $this;
    }
}
