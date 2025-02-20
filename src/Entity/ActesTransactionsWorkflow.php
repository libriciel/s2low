<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

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


}
