<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * MailMessageEmis
 *
 * @ORM\Table(name="mail_message_emis", indexes={@ORM\Index(name="mail_message_emis_mail_transaction_id", columns={"mail_transaction_id"})})
 * @ORM\Entity
 */
class MailMessageEmis
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=128, nullable=false, options={"fixed"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_message_emis_id_seq", allocationSize=1, initialValue=1)
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
     * @ORM\Column(name="email", type="string", length=256, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="type_envoi", type="string", length=64, nullable=false)
     */
    private $typeEnvoi;

    /**
     * @var bool
     *
     * @ORM\Column(name="ack", type="boolean", nullable=false)
     */
    private $ack = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="ack_date", type="datetimetz", nullable=true)
     */
    private $ackDate;

    public function getId(): ?string
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTypeEnvoi(): ?string
    {
        return $this->typeEnvoi;
    }

    public function setTypeEnvoi(string $typeEnvoi): static
    {
        $this->typeEnvoi = $typeEnvoi;

        return $this;
    }

    public function isAck(): ?bool
    {
        return $this->ack;
    }

    public function setAck(bool $ack): static
    {
        $this->ack = $ack;

        return $this;
    }

    public function getAckDate(): ?\DateTimeInterface
    {
        return $this->ackDate;
    }

    public function setAckDate(?\DateTimeInterface $ackDate): static
    {
        $this->ackDate = $ackDate;

        return $this;
    }


}
