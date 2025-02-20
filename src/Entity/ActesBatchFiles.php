<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

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


}
