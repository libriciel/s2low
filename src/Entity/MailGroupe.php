<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailGroupe
 *
 * @ORM\Table(name="mail_groupe", uniqueConstraints={@ORM\UniqueConstraint(name="mail_groupe_unique", columns={"authority_id", "name"})})
 * @ORM\Entity
 */
class MailGroupe
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_groupe_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=true)
     */
    private $authorityId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=false)
     */
    private $name;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }


}
