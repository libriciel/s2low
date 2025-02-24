<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorityGroupSiren
 *
 * @ORM\Table(name="authority_group_siren", indexes={@ORM\Index(name="IDX_81036072BE5846DA", columns={"authority_group_id"})})
 * @ORM\Entity
 */
class AuthorityGroupSiren
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="authority_group_siren_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=10, nullable=true)
     */
    private $siren;

    /**
     * @var AuthorityGroups
     *
     * @ORM\ManyToOne(targetEntity="AuthorityGroups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_group_id", referencedColumnName="id")
     * })
     */
    private $authorityGroup;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getAuthorityGroup(): ?AuthorityGroups
    {
        return $this->authorityGroup;
    }

    public function setAuthorityGroup(?AuthorityGroups $authorityGroup): static
    {
        $this->authorityGroup = $authorityGroup;

        return $this;
    }


}
