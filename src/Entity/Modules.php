<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Modules
 *
 * @ORM\Table(name="modules", uniqueConstraints={@ORM\UniqueConstraint(name="modules_name_idx", columns={"name"})})
 * @ORM\Entity
 */
class Modules
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="modules_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=128, nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="menu_entry", type="string", length=128, nullable=true)
     */
    private $menuEntry;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default"="1"})
     */
    private $status = 1;


}
