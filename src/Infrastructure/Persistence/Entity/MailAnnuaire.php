<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailAnnuaire
 *
 * @ORM\Table(name="mail_annuaire")
 * @ORM\Entity
 */
class MailAnnuaire
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_annuaire_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=false)
     */
    private $authorityId;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_address", type="string", length=128, nullable=false)
     */
    private $mailAddress;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=256, nullable=true)
     */
    private $description;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthorityId(): ?int
    {
        return $this->authorityId;
    }

    public function setAuthorityId(int $authorityId): static
    {
        $this->authorityId = $authorityId;

        return $this;
    }

    public function getMailAddress(): ?string
    {
        return $this->mailAddress;
    }

    public function setMailAddress(string $mailAddress): static
    {
        $this->mailAddress = $mailAddress;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
