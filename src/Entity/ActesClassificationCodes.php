<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActesClassificationCodes
 *
 * @ORM\Table(name="actes_classification_codes", indexes={@ORM\Index(name="acc_ai", columns={"authority_id"}), @ORM\Index(name="IDX_881CE071727ACA70", columns={"parent_id"})})
 * @ORM\Entity
 */
class ActesClassificationCodes
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_classification_codes_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=false)
     */
    private $authorityId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="level", type="integer", nullable=true)
     */
    private $level;

    /**
     * @var int|null
     *
     * @ORM\Column(name="code", type="integer", nullable=true)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=256, nullable=true)
     */
    private $description;

    /**
     * @var ActesClassificationCodes
     *
     * @ORM\ManyToOne(targetEntity="ActesClassificationCodes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;


}
