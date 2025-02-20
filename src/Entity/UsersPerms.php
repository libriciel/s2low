<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersPerms
 *
 * @ORM\Table(name="users_perms", indexes={@ORM\Index(name="IDX_9A553DF8AFC2B591", columns={"module_id"}), @ORM\Index(name="IDX_9A553DF8A76ED395", columns={"user_id"})})
 * @ORM\Entity
 */
class UsersPerms
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="users_perms_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="perm", type="string", length=10, nullable=true)
     */
    private $perm;

    /**
     * @var Modules
     *
     * @ORM\ManyToOne(targetEntity="Modules")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     * })
     */
    private $module;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;


}
