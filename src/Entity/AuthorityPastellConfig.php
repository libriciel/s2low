<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorityPastellConfig
 *
 * @ORM\Table(name="authority_pastell_config", indexes={@ORM\Index(name="IDX_877423F9AFC2B591", columns={"module_id"}), @ORM\Index(name="IDX_877423F981EC865B", columns={"authority_id"})})
 * @ORM\Entity
 */
class AuthorityPastellConfig
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="authority_pastell_config_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="id_flux", type="string", nullable=false)
     */
    private $idFlux;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", nullable=false)
     */
    private $action;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_auto", type="boolean", nullable=false)
     */
    private $isAuto;

    /**
     * @var string|null
     *
     * @ORM\Column(name="destination", type="string", length=16, nullable=true)
     */
    private $destination;

    /**
     * @var int|null
     *
     * @ORM\Column(name="transaction_id_min", type="integer", nullable=true)
     */
    private $transactionIdMin;

    /**
     * @var int
     *
     * @ORM\Column(name="transaction_id_max", type="integer", nullable=false, options={"default"="2147483647"})
     */
    private $transactionIdMax = 2147483647;

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

    public function getIdFlux(): ?string
    {
        return $this->idFlux;
    }

    public function setIdFlux(string $idFlux): static
    {
        $this->idFlux = $idFlux;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function isAuto(): ?bool
    {
        return $this->isAuto;
    }

    public function setIsAuto(bool $isAuto): static
    {
        $this->isAuto = $isAuto;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getTransactionIdMin(): ?int
    {
        return $this->transactionIdMin;
    }

    public function setTransactionIdMin(?int $transactionIdMin): static
    {
        $this->transactionIdMin = $transactionIdMin;

        return $this;
    }

    public function getTransactionIdMax(): ?int
    {
        return $this->transactionIdMax;
    }

    public function setTransactionIdMax(int $transactionIdMax): static
    {
        $this->transactionIdMax = $transactionIdMax;

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
