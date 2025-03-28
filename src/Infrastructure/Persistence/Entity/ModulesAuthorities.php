<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\Authorities;
use S2low\Infrastructure\Persistence\Entity\Modules;

/**
 * ModulesAuthorities
 *
 * @ORM\Table(name="modules_authorities", indexes={@ORM\Index(name="IDX_863DBAE7AFC2B591", columns={"module_id"}), @ORM\Index(name="IDX_863DBAE781EC865B", columns={"authority_id"})})
 * @ORM\Entity
 */
class ModulesAuthorities
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="modules_authorities_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

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
     * @var Authorities
     *
     * @ORM\ManyToOne(targetEntity="Authorities")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_id", referencedColumnName="id")
     * })
     */
    private $authority;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAuthority(): ?Authorities
    {
        return $this->authority;
    }

    public function setAuthority(?Authorities $authority): static
    {
        $this->authority = $authority;

        return $this;
    }
}
