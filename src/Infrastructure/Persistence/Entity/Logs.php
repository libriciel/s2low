<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\Authorities;
use S2low\Infrastructure\Persistence\Entity\AuthorityGroups;

/**
 * Logs
 *
 * @ORM\Table(name="logs", indexes={@ORM\Index(name="logs_user_id_visibility_id_idx", columns={"user_id", "visibility", "id"}), @ORM\Index(name="l_u", columns={"user_id"}), @ORM\Index(name="l_date", columns={"date", "id"}), @ORM\Index(name="authority_index", columns={"authority_id", "id"}), @ORM\Index(name="authority_group_index", columns={"authority_group_id", "id"}), @ORM\Index(name="IDX_F08FC65CBE5846DA", columns={"authority_group_id"}), @ORM\Index(name="IDX_F08FC65C81EC865B", columns={"authority_id"})})
 * @ORM\Entity
 */
class Logs
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="logs_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetimetz", nullable=false)
     */
    private $date;

    /**
     * @var int|null
     *
     * @ORM\Column(name="severity", type="integer", nullable=true)
     */
    private $severity;

    /**
     * @var string|null
     *
     * @ORM\Column(name="module", type="string", length=50, nullable=true)
     */
    private $module;

    /**
     * @var string|null
     *
     * @ORM\Column(name="issuer", type="string", length=30, nullable=true)
     */
    private $issuer;

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="visibility", type="string", length=5, nullable=true)
     */
    private $visibility;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var string|null
     *
     * @ORM\Column(name="timestamp", type="text", nullable=true)
     */
    private $timestamp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message_horodate", type="text", nullable=true)
     */
    private $messageHorodate;

    /**
     * @var AuthorityGroups
     *
     * @ORM\ManyToOne(targetEntity="AuthorityGroups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_group_id", referencedColumnName="id")
     * })
     */
    private $authorityGroup;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getSeverity(): ?int
    {
        return $this->severity;
    }

    public function setSeverity(?int $severity): static
    {
        $this->severity = $severity;

        return $this;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function setModule(?string $module): static
    {
        $this->module = $module;

        return $this;
    }

    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    public function setIssuer(?string $issuer): static
    {
        $this->issuer = $issuer;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(?string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    public function setTimestamp(?string $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getMessageHorodate(): ?string
    {
        return $this->messageHorodate;
    }

    public function setMessageHorodate(?string $messageHorodate): static
    {
        $this->messageHorodate = $messageHorodate;

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
