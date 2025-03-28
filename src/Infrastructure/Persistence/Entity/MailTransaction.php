<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * MailTransaction
 *
 * @ORM\Table(name="mail_transaction", uniqueConstraints={@ORM\UniqueConstraint(name="mail_transaction_filename_id", columns={"fn_download", "id"})}, indexes={@ORM\Index(name="mt_ui", columns={"user_id"})})
 * @ORM\Entity
 */
class MailTransaction
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_transaction_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="objet", type="string", length=1024, nullable=false)
     */
    private $objet;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=2048, nullable=false)
     */
    private $message;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fn_download", type="string", length=512, nullable=true)
     */
    private $fnDownload;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=40, nullable=false)
     */
    private $status;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_envoi", type="datetimetz", nullable=true)
     */
    private $dateEnvoi;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=64, nullable=true)
     */
    private $password;

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

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): static
    {
        $this->objet = $objet;

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

    public function getFnDownload(): ?string
    {
        return $this->fnDownload;
    }

    public function setFnDownload(?string $fnDownload): static
    {
        $this->fnDownload = $fnDownload;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDateEnvoi(): ?\DateTimeInterface
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(?\DateTimeInterface $dateEnvoi): static
    {
        $this->dateEnvoi = $dateEnvoi;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

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
