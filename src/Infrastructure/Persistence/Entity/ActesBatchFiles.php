<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\ActesBatches;
use S2low\Infrastructure\Persistence\Entity\ActesTransactions;

/**
 * ActesBatchFiles
 *
 * @ORM\Table(name="actes_batch_files", indexes={@ORM\Index(name="IDX_A08AA5EB2FC0CB0F", columns={"transaction_id"}), @ORM\Index(name="IDX_A08AA5EBF39EBE7A", columns={"batch_id"})})
 * @ORM\Entity
 */
class ActesBatchFiles
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_batch_files_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filename", type="string", length=1024, nullable=true)
     */
    private $filename;

    /**
     * @var int|null
     *
     * @ORM\Column(name="filesize", type="integer", nullable=true)
     */
    private $filesize;

    /**
     * @var string|null
     *
     * @ORM\Column(name="status", type="string", length=10, nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="signature", type="text", nullable=true)
     */
    private $signature;

    /**
     * @var ActesTransactions
     *
     * @ORM\ManyToOne(targetEntity="ActesTransactions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="transaction_id", referencedColumnName="id")
     * })
     */
    private $transaction;

    /**
     * @var ActesBatches
     *
     * @ORM\ManyToOne(targetEntity="ActesBatches")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="batch_id", referencedColumnName="id")
     * })
     */
    private $batch;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFilesize(): ?int
    {
        return $this->filesize;
    }

    public function setFilesize(?int $filesize): static
    {
        $this->filesize = $filesize;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function getTransaction(): ?ActesTransactions
    {
        return $this->transaction;
    }

    public function setTransaction(?ActesTransactions $transaction): static
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getBatch(): ?ActesBatches
    {
        return $this->batch;
    }

    public function setBatch(?ActesBatches $batch): static
    {
        $this->batch = $batch;

        return $this;
    }
}
