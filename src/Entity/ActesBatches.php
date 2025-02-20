<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActesBatches
 *
 * @ORM\Table(name="actes_batches")
 * @ORM\Entity
 */
class ActesBatches
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_batches_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="submission_date", type="datetimetz", nullable=true)
     */
    private $submissionDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="storage_dir", type="string", length=1024, nullable=true)
     */
    private $storageDir;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=1024, nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="num_prefix", type="string", length=16, nullable=true)
     */
    private $numPrefix;

    /**
     * @var int|null
     *
     * @ORM\Column(name="next_suffix", type="integer", nullable=true)
     */
    private $nextSuffix;


}
