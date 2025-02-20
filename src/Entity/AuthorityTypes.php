<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorityTypes
 *
 * @ORM\Table(name="authority_types", indexes={@ORM\Index(name="IDX_E4B98219B704F8D5", columns={"parent_type_id"})})
 * @ORM\Entity
 */
class AuthorityTypes
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="authority_types_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=512, nullable=true)
     */
    private $description;

    /**
     * @var AuthorityTypes
     *
     * @ORM\ManyToOne(targetEntity="AuthorityTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_type_id", referencedColumnName="id")
     * })
     */
    private $parentType;


}
