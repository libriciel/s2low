<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\Authorities;

/**
 * HeliosRetour
 *
 * @ORM\Table(name="helios_retour", uniqueConstraints={@ORM\UniqueConstraint(name="helios_retour_filename_id", columns={"filename", "id"})}, indexes={@ORM\Index(name="hr_id_is_incloud", columns={"is_in_cloud", "not_available", "id"}), @ORM\Index(name="IDX_B47C4C3681EC865B", columns={"authority_id"})})
 * @ORM\Entity
 */
class HeliosRetour
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="helios_retour_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=255, nullable=true)
     */
    private $siren;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetimetz", nullable=true)
     */
    private $date;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siret", type="string", length=14, nullable=true, options={"fixed"=true})
     */
    private $siret;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sha1", type="string", length=256, nullable=true)
     */
    private $sha1;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_in_cloud", type="boolean", nullable=false)
     */
    private $isInCloud = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="not_available", type="boolean", nullable=false)
     */
    private $notAvailable = false;

    /**
     * @var int|null
     *
     * @ORM\Column(name="file_size", type="integer", nullable=true)
     */
    private $fileSize;

    /**
     * @var Authorities
     *
     * @ORM\ManyToOne(targetEntity="Authorities")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_id", referencedColumnName="id")
     * })
     */
    private $authority;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getSha1(): ?string
    {
        return $this->sha1;
    }

    public function setSha1(?string $sha1): static
    {
        $this->sha1 = $sha1;

        return $this;
    }

    public function isInCloud(): ?bool
    {
        return $this->isInCloud;
    }

    public function setIsInCloud(bool $isInCloud): static
    {
        $this->isInCloud = $isInCloud;

        return $this;
    }

    public function isNotAvailable(): ?bool
    {
        return $this->notAvailable;
    }

    public function setNotAvailable(bool $notAvailable): static
    {
        $this->notAvailable = $notAvailable;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

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


}
