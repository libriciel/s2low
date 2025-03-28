<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ActesEnvelopes
 *
 * @ORM\Table(name="actes_envelopes", uniqueConstraints={@ORM\UniqueConstraint(name="ae_id_is_in_cloud", columns={"is_in_cloud", "not_available", "id"}), @ORM\UniqueConstraint(name="actes_envelopes_submission_date_id", columns={"submission_date", "id"})})
 * @ORM\Entity
 */
class ActesEnvelopes
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_envelopes_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="submission_date", type="datetimetz", nullable=true)
     */
    private $submissionDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=128, nullable=true)
     */
    private $siren;

    /**
     * @var string|null
     *
     * @ORM\Column(name="department", type="string", length=3, nullable=true, options={"fixed"=true})
     */
    private $department;

    /**
     * @var string|null
     *
     * @ORM\Column(name="district", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $district;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_type_code", type="integer", nullable=true)
     */
    private $authorityTypeCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="telephone", type="string", length=25, nullable=true)
     */
    private $telephone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="file_path", type="string", length=1024, nullable=true)
     */
    private $filePath;

    /**
     * @var int|null
     *
     * @ORM\Column(name="file_size", type="integer", nullable=true)
     */
    private $fileSize;

    /**
     * @var string|null
     *
     * @ORM\Column(name="return_mail", type="string", length=1024, nullable=true)
     */
    private $returnMail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="warning_sent", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $warningSent;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getSubmissionDate(): ?\DateTimeInterface
    {
        return $this->submissionDate;
    }

    public function setSubmissionDate(?\DateTimeInterface $submissionDate): static
    {
        $this->submissionDate = $submissionDate;

        return $this;
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

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): static
    {
        $this->district = $district;

        return $this;
    }

    public function getAuthorityTypeCode(): ?int
    {
        return $this->authorityTypeCode;
    }

    public function setAuthorityTypeCode(?int $authorityTypeCode): static
    {
        $this->authorityTypeCode = $authorityTypeCode;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

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

    public function getReturnMail(): ?string
    {
        return $this->returnMail;
    }

    public function setReturnMail(?string $returnMail): static
    {
        $this->returnMail = $returnMail;

        return $this;
    }

    public function getWarningSent(): ?string
    {
        return $this->warningSent;
    }

    public function setWarningSent(?string $warningSent): static
    {
        $this->warningSent = $warningSent;

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
}
