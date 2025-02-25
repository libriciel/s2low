<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\Modules;
use S2low\Infrastructure\Persistence\Entity\Users;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerm(): ?string
    {
        return $this->perm;
    }

    public function setPerm(?string $perm): static
    {
        $this->perm = $perm;

        return $this;
    }

    public function getModule(): ?Modules
    {
        return $this->module;
    }

    public function setModule(?Modules $module): static
    {
        $this->module = $module;

        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): static
    {
        $this->user = $user;

        return $this;
    }


}
