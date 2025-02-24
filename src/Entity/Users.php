<?php

namespace S2low\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Users
 *
 * @ORM\Table(name="users", uniqueConstraints={@ORM\UniqueConstraint(name="users_certificate_login", columns={"subject_dn", "issuer_dn", "login"})}, indexes={@ORM\Index(name="users_login", columns={"login"}), @ORM\Index(name="users_certificate_hash_idx", columns={"certificate_hash"}), @ORM\Index(name="u_authority_id", columns={"authority_id", "id"}), @ORM\Index(name="IDX_1483A5E9BE5846DA", columns={"authority_group_id"}), @ORM\Index(name="IDX_1483A5E981EC865B", columns={"authority_id"})})
 * @ORM\Entity
 */
class Users
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="users_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="subject_dn", type="string", length=512, nullable=false)
     */
    private $subjectDn;

    /**
     * @var string
     *
     * @ORM\Column(name="issuer_dn", type="string", length=512, nullable=false)
     */
    private $issuerDn;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="givenname", type="string", length=100, nullable=true)
     */
    private $givenname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="telephone", type="string", length=25, nullable=true)
     */
    private $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=5, nullable=false)
     */
    private $role;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="certificate", type="text", nullable=true)
     */
    private $certificate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="cert_not_before", type="datetimetz", nullable=true)
     */
    private $certNotBefore;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="cert_not_after", type="datetimetz", nullable=true)
     */
    private $certNotAfter;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cert_serial", type="string", length=100, nullable=true)
     */
    private $certSerial;

    /**
     * @var string|null
     *
     * @ORM\Column(name="login", type="string", length=128, nullable=true)
     */
    private $login;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @var string|null
     *
     * @ORM\Column(name="certificate_rgs_2_etoiles", type="text", nullable=true)
     */
    private $certificateRgs2Etoiles;

    /**
     * @var string|null
     *
     * @ORM\Column(name="certificate_hash", type="string", length=64, nullable=true)
     */
    private $certificateHash;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSubjectDn(): ?string
    {
        return $this->subjectDn;
    }

    public function setSubjectDn(string $subjectDn): static
    {
        $this->subjectDn = $subjectDn;

        return $this;
    }

    public function getIssuerDn(): ?string
    {
        return $this->issuerDn;
    }

    public function setIssuerDn(string $issuerDn): static
    {
        $this->issuerDn = $issuerDn;

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

    public function getGivenname(): ?string
    {
        return $this->givenname;
    }

    public function setGivenname(?string $givenname): static
    {
        $this->givenname = $givenname;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    public function setCertificate(?string $certificate): static
    {
        $this->certificate = $certificate;

        return $this;
    }

    public function getCertNotBefore(): ?\DateTimeInterface
    {
        return $this->certNotBefore;
    }

    public function setCertNotBefore(?\DateTimeInterface $certNotBefore): static
    {
        $this->certNotBefore = $certNotBefore;

        return $this;
    }

    public function getCertNotAfter(): ?\DateTimeInterface
    {
        return $this->certNotAfter;
    }

    public function setCertNotAfter(?\DateTimeInterface $certNotAfter): static
    {
        $this->certNotAfter = $certNotAfter;

        return $this;
    }

    public function getCertSerial(): ?string
    {
        return $this->certSerial;
    }

    public function setCertSerial(?string $certSerial): static
    {
        $this->certSerial = $certSerial;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCertificateRgs2Etoiles(): ?string
    {
        return $this->certificateRgs2Etoiles;
    }

    public function setCertificateRgs2Etoiles(?string $certificateRgs2Etoiles): static
    {
        $this->certificateRgs2Etoiles = $certificateRgs2Etoiles;

        return $this;
    }

    public function getCertificateHash(): ?string
    {
        return $this->certificateHash;
    }

    public function setCertificateHash(?string $certificateHash): static
    {
        $this->certificateHash = $certificateHash;

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
