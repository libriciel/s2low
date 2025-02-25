<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\ActesNatures;

/**
 * ActesTypePj
 *
 * @ORM\Table(name="actes_type_pj", indexes={@ORM\Index(name="IDX_9B58DC6C3BCB2E4B", columns={"nature_id"})})
 * @ORM\Entity
 */
class ActesTypePj
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_type_pj_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=5, nullable=true)
     */
    private $code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="libelle", type="string", length=128, nullable=true)
     */
    private $libelle;

    /**
     * @var ActesNatures
     *
     * @ORM\ManyToOne(targetEntity="ActesNatures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="nature_id", referencedColumnName="id")
     * })
     */
    private $nature;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getNature(): ?ActesNatures
    {
        return $this->nature;
    }

    public function setNature(?ActesNatures $nature): static
    {
        $this->nature = $nature;

        return $this;
    }


}
