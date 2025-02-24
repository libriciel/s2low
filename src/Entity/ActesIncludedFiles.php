<?php

namespace S2low\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ActesIncludedFiles
 *
 * @ORM\Table(name="actes_included_files", indexes={@ORM\Index(name="aif_ti", columns={"transaction_id"}), @ORM\Index(name="aif_ei", columns={"envelope_id"}), @ORM\Index(name="actes_included_files_filename", columns={"filename"})})
 * @ORM\Entity
 */
class ActesIncludedFiles
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_included_files_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filename", type="string", length=512, nullable=true)
     */
    private $filename;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filetype", type="string", length=512, nullable=true)
     */
    private $filetype;

    /**
     * @var int|null
     *
     * @ORM\Column(name="filesize", type="integer", nullable=true)
     */
    private $filesize;

    /**
     * @var string|null
     *
     * @ORM\Column(name="signature", type="text", nullable=true)
     */
    private $signature;

    /**
     * @var string|null
     *
     * @ORM\Column(name="posted_filename", type="string", length=512, nullable=true)
     */
    private $postedFilename;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sha1", type="string", length=256, nullable=true)
     */
    private $sha1 = '';

    /**
     * @var string|null
     *
     * @ORM\Column(name="code_pj", type="string", length=5, nullable=true)
     */
    private $codePj;

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
     * @var ActesEnvelopes
     *
     * @ORM\ManyToOne(targetEntity="ActesEnvelopes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="envelope_id", referencedColumnName="id")
     * })
     */
    private $envelope;

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

    public function getFiletype(): ?string
    {
        return $this->filetype;
    }

    public function setFiletype(?string $filetype): static
    {
        $this->filetype = $filetype;

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

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function getPostedFilename(): ?string
    {
        return $this->postedFilename;
    }

    public function setPostedFilename(?string $postedFilename): static
    {
        $this->postedFilename = $postedFilename;

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

    public function getCodePj(): ?string
    {
        return $this->codePj;
    }

    public function setCodePj(?string $codePj): static
    {
        $this->codePj = $codePj;

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

    public function getEnvelope(): ?ActesEnvelopes
    {
        return $this->envelope;
    }

    public function setEnvelope(?ActesEnvelopes $envelope): static
    {
        $this->envelope = $envelope;

        return $this;
    }


}
