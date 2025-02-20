<?php

namespace S2low\Entity;

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


}
