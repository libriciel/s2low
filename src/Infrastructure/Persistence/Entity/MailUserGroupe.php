<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\MailAnnuaire;
use S2low\Infrastructure\Persistence\Entity\MailGroupe;

/**
 * MailUserGroupe
 *
 * @ORM\Table(name="mail_user_groupe", uniqueConstraints={@ORM\UniqueConstraint(name="mail_user_groupe_unique", columns={"id_user", "id_groupe"})}, indexes={@ORM\Index(name="IDX_D534EAC16B3CA4B", columns={"id_user"}), @ORM\Index(name="IDX_D534EAC1228E39CC", columns={"id_groupe"})})
 * @ORM\Entity
 */
class MailUserGroupe
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_user_groupe_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \MailAnnuaire
     *
     * @ORM\ManyToOne(targetEntity="MailAnnuaire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     * })
     */
    private $idUser;

    /**
     * @var MailGroupe
     *
     * @ORM\ManyToOne(targetEntity="MailGroupe")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_groupe", referencedColumnName="id")
     * })
     */
    private $idGroupe;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?MailAnnuaire
    {
        return $this->idUser;
    }

    public function setIdUser(?MailAnnuaire $idUser): static
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getIdGroupe(): ?MailGroupe
    {
        return $this->idGroupe;
    }

    public function setIdGroupe(?MailGroupe $idGroupe): static
    {
        $this->idGroupe = $idGroupe;

        return $this;
    }
}
