<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActesNatures
 *
 * @ORM\Table(name="actes_natures")
 * @ORM\Entity
 */
class ActesNatures
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_natures_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="short_descr", type="string", length=5, nullable=true)
     */
    private $shortDescr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="descr", type="string", length=128, nullable=true)
     */
    private $descr;


}
