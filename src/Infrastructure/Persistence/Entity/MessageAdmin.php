<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\Users;

/**
 * MessageAdmin
 *
 * @ORM\Table(name="message_admin", indexes={@ORM\Index(name="IDX_77A8F29DA76ED395", columns={"user_id"}), @ORM\Index(name="IDX_77A8F29D736D8C89", columns={"user_id_publieur"}), @ORM\Index(name="IDX_77A8F29DCCE1A952", columns={"user_id_retireur"})})
 * @ORM\Entity
 */
class MessageAdmin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="message_admin_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="titre", type="text", nullable=false)
     */
    private $titre;

    /**
     * @var int|null
     *
     * @ORM\Column(name="niveau", type="integer", nullable=true, options={"default"="1"})
     */
    private $niveau = 1;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_publication", type="datetimetz", nullable=true)
     */
    private $datePublication;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_retrait", type="datetimetz", nullable=true)
     */
    private $dateRetrait;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=false)
     */
    private $message;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_publie", type="boolean", nullable=true)
     */
    private $isPublie = false;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_retire", type="boolean", nullable=true)
     */
    private $isRetire = false;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id_publieur", referencedColumnName="id")
     * })
     */
    private $userIdPublieur;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id_retireur", referencedColumnName="id")
     * })
     */
    private $userIdRetireur;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    public function setNiveau(?int $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;

        return $this;
    }

    public function getDateRetrait(): ?\DateTimeInterface
    {
        return $this->dateRetrait;
    }

    public function setDateRetrait(?\DateTimeInterface $dateRetrait): static
    {
        $this->dateRetrait = $dateRetrait;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isPublie(): ?bool
    {
        return $this->isPublie;
    }

    public function setIsPublie(?bool $isPublie): static
    {
        $this->isPublie = $isPublie;

        return $this;
    }

    public function isRetire(): ?bool
    {
        return $this->isRetire;
    }

    public function setIsRetire(?bool $isRetire): static
    {
        $this->isRetire = $isRetire;

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

    public function getUserIdPublieur(): ?Users
    {
        return $this->userIdPublieur;
    }

    public function setUserIdPublieur(?Users $userIdPublieur): static
    {
        $this->userIdPublieur = $userIdPublieur;

        return $this;
    }

    public function getUserIdRetireur(): ?Users
    {
        return $this->userIdRetireur;
    }

    public function setUserIdRetireur(?Users $userIdRetireur): static
    {
        $this->userIdRetireur = $userIdRetireur;

        return $this;
    }
}
