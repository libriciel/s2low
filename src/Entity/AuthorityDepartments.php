<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorityDepartments
 *
 * @ORM\Table(name="authority_departments")
 * @ORM\Entity
 */
class AuthorityDepartments
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="authority_departments_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=3, nullable=true)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=true)
     */
    private $name;


}
