<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorityGroups
 *
 * @ORM\Table(name="authority_groups")
 * @ORM\Entity
 */
class AuthorityGroups
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="authority_groups_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=true)
     */
    private $name;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;


}
