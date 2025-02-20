<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActesTypePj
 *
 * @ORM\Table(name="actes_type_pj", indexes={@ORM\Index(name="IDX_9B58DC6C3BCB2E4B", columns={"nature_id"})})
 * @ORM\Entity
 */
class ActesTypePj
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_type_pj_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=5, nullable=true)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="libelle", type="string", length=128, nullable=true)
     */
    private $libelle;

    /**
     * @var ActesNatures
     *
     * @ORM\ManyToOne(targetEntity="ActesNatures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="nature_id", referencedColumnName="id")
     * })
     */
    private $nature;


}
