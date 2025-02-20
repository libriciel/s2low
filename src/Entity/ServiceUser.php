<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ServiceUser
 *
 * @ORM\Table(name="service_user", indexes={@ORM\Index(name="IDX_43D062A5727ACA70", columns={"parent_id"})})
 * @ORM\Entity
 */
class ServiceUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="service_user_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=true)
     */
    private $authorityId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=false)
     */
    private $name;

    /**
     * @var ServiceUser
     *
     * @ORM\ManyToOne(targetEntity="ServiceUser")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;


}
