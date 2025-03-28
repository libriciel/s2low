<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * HeliosTransactionsWorkflow
 *
 * @ORM\Table(name="helios_transactions_workflow", indexes={@ORM\Index(name="helios_transactions_workflow_transaction_id_idx", columns={"transaction_id"}), @ORM\Index(name="helios_transactions_workflow_status_id_idx", columns={"status_id"}), @ORM\Index(name="helios_transactions_workflow_date_idx", columns={"date"})})
 * @ORM\Entity
 */
class HeliosTransactionsWorkflow
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="helios_transactions_workflow_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="transaction_id", type="integer", nullable=false)
     */
    private $transactionId;

    /**
     * @var int
     *
     * @ORM\Column(name="status_id", type="integer", nullable=false)
     */
    private $statusId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetimetz", nullable=false)
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=512, nullable=false)
     */
    private $message;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): ?int
    {
        return $this->transactionId;
    }

    public function setTransactionId(int $transactionId): static
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getStatusId(): ?int
    {
        return $this->statusId;
    }

    public function setStatusId(int $statusId): static
    {
        $this->statusId = $statusId;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

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
}
