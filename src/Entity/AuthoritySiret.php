<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthoritySiret
 *
 * @ORM\Table(name="authority_siret", indexes={@ORM\Index(name="IDX_9B60485B81EC865B", columns={"authority_id"})})
 * @ORM\Entity
 */
class AuthoritySiret
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="authority_siret_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siret", type="string", length=14, nullable=true, options={"fixed"=true})
     */
    private $siret;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetimetz", nullable=true)
     */
    private $date;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_blocked", type="boolean", nullable=false)
     */
    private $isBlocked = false;

    /**
     * @var Authorities
     *
     * @ORM\ManyToOne(targetEntity="Authorities")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_id", referencedColumnName="id")
     * })
     */
    private $authority;


}
