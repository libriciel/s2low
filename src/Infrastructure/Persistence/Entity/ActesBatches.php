<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ActesBatches
 *
 * @ORM\Table(name="actes_batches")
 * @ORM\Entity
 */
class ActesBatches
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_batches_id_seq", allocationSize=1, initialValue=1)
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
     * @ORM\Column(name="storage_dir", type="string", length=1024, nullable=true)
     */
    private $storageDir;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=1024, nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="num_prefix", type="string", length=16, nullable=true)
     */
    private $numPrefix;

    /**
     * @var int|null
     *
     * @ORM\Column(name="next_suffix", type="integer", nullable=true)
     */
    private $nextSuffix;

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

    public function getStorageDir(): ?string
    {
        return $this->storageDir;
    }

    public function setStorageDir(?string $storageDir): static
    {
        $this->storageDir = $storageDir;

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

    public function getNumPrefix(): ?string
    {
        return $this->numPrefix;
    }

    public function setNumPrefix(?string $numPrefix): static
    {
        $this->numPrefix = $numPrefix;

        return $this;
    }

    public function getNextSuffix(): ?int
    {
        return $this->nextSuffix;
    }

    public function setNextSuffix(?int $nextSuffix): static
    {
        $this->nextSuffix = $nextSuffix;

        return $this;
    }
}
