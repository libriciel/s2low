<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailIncludedFile
 *
 * @ORM\Table(name="mail_included_file")
 * @ORM\Entity
 */
class MailIncludedFile
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_included_file_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="mail_transaction_id", type="integer", nullable=false)
     */
    private $mailTransactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=512, nullable=false)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="filetype", type="string", length=64, nullable=false)
     */
    private $filetype;

    /**
     * @var int
     *
     * @ORM\Column(name="filesize", type="integer", nullable=false)
     */
    private $filesize;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMailTransactionId(): ?int
    {
        return $this->mailTransactionId;
    }

    public function setMailTransactionId(int $mailTransactionId): static
    {
        $this->mailTransactionId = $mailTransactionId;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFiletype(): ?string
    {
        return $this->filetype;
    }

    public function setFiletype(string $filetype): static
    {
        $this->filetype = $filetype;

        return $this;
    }

    public function getFilesize(): ?int
    {
        return $this->filesize;
    }

    public function setFilesize(int $filesize): static
    {
        $this->filesize = $filesize;

        return $this;
    }


}
