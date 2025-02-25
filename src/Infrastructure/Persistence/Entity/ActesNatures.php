<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActesNatures
 *
 * @ORM\Table(name="actes_natures")
 * @ORM\Entity
 */
class ActesNatures
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_natures_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="short_descr", type="string", length=5, nullable=true)
     */
    private $shortDescr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="descr", type="string", length=128, nullable=true)
     */
    private $descr;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShortDescr(): ?string
    {
        return $this->shortDescr;
    }

    public function setShortDescr(?string $shortDescr): static
    {
        $this->shortDescr = $shortDescr;

        return $this;
    }

    public function getDescr(): ?string
    {
        return $this->descr;
    }

    public function setDescr(?string $descr): static
    {
        $this->descr = $descr;

        return $this;
    }


}
