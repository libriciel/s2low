<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\ActesStatus;
use S2low\Infrastructure\Persistence\Entity\ActesTransactions;

/**
 * ActesTransactionsWorkflow
 *
 * @ORM\Table(name="actes_transactions_workflow", indexes={@ORM\Index(name="atw_tid_idx", columns={"transaction_id"}), @ORM\Index(name="atw_id_date", columns={"transaction_id", "date", "id"}), @ORM\Index(name="actes_transactions_workflow_date_idx", columns={"date"}), @ORM\Index(name="IDX_98B872DF6BF700BD", columns={"status_id"})})
 * @ORM\Entity
 */
class ActesTransactionsWorkflow
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_transactions_workflow_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetimetz", nullable=true)
     */
    private $date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="string", length=512, nullable=true)
     */
    private $message;

    /**
     * @var string|null
     *
     * @ORM\Column(name="flux_retour", type="blob", nullable=true)
     */
    private $fluxRetour;

    /**
     * @var string|null
     *
     * @ORM\Column(name="flux_retour_texte", type="text", nullable=true)
     */
    private $fluxRetourTexte;

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
     * @var ActesStatus
     *
     * @ORM\ManyToOne(targetEntity="ActesStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getFluxRetour()
    {
        return $this->fluxRetour;
    }

    public function setFluxRetour($fluxRetour): static
    {
        $this->fluxRetour = $fluxRetour;

        return $this;
    }

    public function getFluxRetourTexte(): ?string
    {
        return $this->fluxRetourTexte;
    }

    public function setFluxRetourTexte(?string $fluxRetourTexte): static
    {
        $this->fluxRetourTexte = $fluxRetourTexte;

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

    public function getStatus(): ?ActesStatus
    {
        return $this->status;
    }

    public function setStatus(?ActesStatus $status): static
    {
        $this->status = $status;

        return $this;
    }


}
