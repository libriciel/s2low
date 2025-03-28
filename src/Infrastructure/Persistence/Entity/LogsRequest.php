<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\Authorities;
use S2low\Infrastructure\Persistence\Entity\AuthorityGroups;
use S2low\Infrastructure\Persistence\Entity\Users;

/**
 * LogsRequest
 *
 * @ORM\Table(name="logs_request", indexes={@ORM\Index(name="logs_request_user_id_demandeur_idx", columns={"user_id_demandeur"}), @ORM\Index(name="logs_request_state_idx", columns={"state"}), @ORM\Index(name="IDX_4549D48A81EC865B", columns={"authority_id"}), @ORM\Index(name="IDX_4549D48ABE5846DA", columns={"authority_group_id"}), @ORM\Index(name="IDX_4549D48AA76ED395", columns={"user_id"})})
 * @ORM\Entity
 */
class LogsRequest
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="logs_request_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="state", type="integer", nullable=false)
     */
    private $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_demande", type="datetimetz", nullable=false)
     */
    private $dateDemande;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_traitement", type="datetimetz", nullable=true)
     */
    private $dateTraitement;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_debut", type="date", nullable=false)
     */
    private $dateDebut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_fin", type="date", nullable=false)
     */
    private $dateFin;

    /**
     * @var Authorities
     *
     * @ORM\ManyToOne(targetEntity="Authorities")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_id", referencedColumnName="id")
     * })
     */
    private $authority;

    /**
     * @var AuthorityGroups
     *
     * @ORM\ManyToOne(targetEntity="AuthorityGroups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_group_id", referencedColumnName="id")
     * })
     */
    private $authorityGroup;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id_demandeur", referencedColumnName="id")
     * })
     */
    private $userIdDemandeur;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getDateDemande(): ?\DateTimeInterface
    {
        return $this->dateDemande;
    }

    public function setDateDemande(\DateTimeInterface $dateDemande): static
    {
        $this->dateDemande = $dateDemande;

        return $this;
    }

    public function getDateTraitement(): ?\DateTimeInterface
    {
        return $this->dateTraitement;
    }

    public function setDateTraitement(?\DateTimeInterface $dateTraitement): static
    {
        $this->dateTraitement = $dateTraitement;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getAuthority(): ?Authorities
    {
        return $this->authority;
    }

    public function setAuthority(?Authorities $authority): static
    {
        $this->authority = $authority;

        return $this;
    }

    public function getAuthorityGroup(): ?AuthorityGroups
    {
        return $this->authorityGroup;
    }

    public function setAuthorityGroup(?AuthorityGroups $authorityGroup): static
    {
        $this->authorityGroup = $authorityGroup;

        return $this;
    }

    public function getUserIdDemandeur(): ?Users
    {
        return $this->userIdDemandeur;
    }

    public function setUserIdDemandeur(?Users $userIdDemandeur): static
    {
        $this->userIdDemandeur = $userIdDemandeur;

        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): static
    {
        $this->user = $user;

        return $this;
    }
}
