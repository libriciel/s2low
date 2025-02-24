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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMenuEntry(): ?string
    {
        return $this->menuEntry;
    }

    public function setMenuEntry(?string $menuEntry): static
    {
        $this->menuEntry = $menuEntry;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }


}
