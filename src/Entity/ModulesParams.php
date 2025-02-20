<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ModulesParams
 *
 * @ORM\Table(name="modules_params", indexes={@ORM\Index(name="IDX_735FA070AFC2B591", columns={"module_id"})})
 * @ORM\Entity
 */
class ModulesParams
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="modules_params_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="value", type="string", length=256, nullable=true)
     */
    private $value;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="string", length=512, nullable=true)
     */
    private $description;

    /**
     * @var Modules
     *
     * @ORM\ManyToOne(targetEntity="Modules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     * })
     */
    private $module;


}
