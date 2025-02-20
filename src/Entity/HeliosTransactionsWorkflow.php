<?php

namespace S2low\Entity;

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


}
