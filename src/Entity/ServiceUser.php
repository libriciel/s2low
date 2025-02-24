<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ServiceUser
 *
 * @ORM\Table(name="service_user", indexes={@ORM\Index(name="IDX_43D062A5727ACA70", columns={"parent_id"})})
 * @ORM\Entity
 */
class ServiceUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="service_user_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=true)
     */
    private $authorityId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=false)
     */
    private $name;

    /**
     * @var ServiceUser
     *
     * @ORM\ManyToOne(targetEntity="ServiceUser")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthorityId(): ?int
    {
        return $this->authorityId;
    }

    public function setAuthorityId(?int $authorityId): static
    {
        $this->authorityId = $authorityId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }


}
