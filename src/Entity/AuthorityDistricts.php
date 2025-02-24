<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorityDistricts
 *
 * @ORM\Table(name="authority_districts")
 * @ORM\Entity
 */
class AuthorityDistricts
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="authority_districts_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_department_id", type="integer", nullable=true)
     */
    private $authorityDepartmentId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=1, nullable=true)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=true)
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthorityDepartmentId(): ?int
    {
        return $this->authorityDepartmentId;
    }

    public function setAuthorityDepartmentId(?int $authorityDepartmentId): static
    {
        $this->authorityDepartmentId = $authorityDepartmentId;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }


}
