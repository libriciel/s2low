<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\AuthorityGroups;
use S2low\Infrastructure\Persistence\Entity\AuthorityTypes;

/**
 * Authorities
 *
 * @ORM\Table(name="authorities", indexes={@ORM\Index(name="IDX_991762E5BE5846DA", columns={"authority_group_id"}), @ORM\Index(name="IDX_991762E516D87779", columns={"authority_type_id"})})
 * @ORM\Entity
 */
class Authorities
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="authorities_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ext_siret", type="string", length=5, nullable=true)
     */
    private $extSiret;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=10, nullable=true)
     */
    private $siren;

    /**
     * @var string|null
     *
     * @ORM\Column(name="agreement", type="string", length=64, nullable=true)
     */
    private $agreement;

    /**
     * @var string|null
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string|null
     *
     * @ORM\Column(name="postal_code", type="string", length=20, nullable=true, options={"fixed"=true})
     */
    private $postalCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string|null
     *
     * @ORM\Column(name="telephone", type="string", length=25, nullable=true)
     */
    private $telephone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fax", type="string", length=25, nullable=true)
     */
    private $fax;

    /**
     * @var string|null
     *
     * @ORM\Column(name="department", type="string", length=3, nullable=true, options={"fixed"=true})
     */
    private $department;

    /**
     * @var string|null
     *
     * @ORM\Column(name="district", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $district;

    /**
     * @var string|null
     *
     * @ORM\Column(name="broadcast_email", type="text", nullable=true)
     */
    private $broadcastEmail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email_mail_securise", type="string", length=256, nullable=true)
     */
    private $emailMailSecurise;

    /**
     * @var string|null
     *
     * @ORM\Column(name="default_broadcast_email", type="text", nullable=true)
     */
    private $defaultBroadcastEmail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="helios_ftp_password", type="string", length=128, nullable=true)
     */
    private $heliosFtpPassword;

    /**
     * @var string|null
     *
     * @ORM\Column(name="helios_ftp_login", type="string", length=128, nullable=true)
     */
    private $heliosFtpLogin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="helios_ftp_dest", type="string", length=128, nullable=true)
     */
    private $heliosFtpDest;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_wsdl", type="string", length=128, nullable=true)
     */
    private $saeWsdl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_login", type="string", length=128, nullable=true)
     */
    private $saeLogin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_password", type="string", length=128, nullable=true)
     */
    private $saePassword;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_id_versant", type="text", nullable=true)
     */
    private $saeIdVersant;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_id_archive", type="text", nullable=true)
     */
    private $saeIdArchive;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_numero_aggrement", type="string", length=128, nullable=true)
     */
    private $saeNumeroAggrement;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_originating_agency", type="text", nullable=true)
     */
    private $saeOriginatingAgency;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="new_notification", type="boolean", nullable=true)
     */
    private $newNotification = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pastell_url", type="string", length=1024, nullable=true)
     */
    private $pastellUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pastell_login", type="string", length=255, nullable=true)
     */
    private $pastellLogin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="pastell_password", type="string", length=255, nullable=true)
     */
    private $pastellPassword;

    /**
     * @var int|null
     *
     * @ORM\Column(name="pastell_id_e", type="integer", nullable=true)
     */
    private $pastellIdE;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="helios_do_not_verify_nom_fic_unicity", type="boolean", nullable=true)
     */
    private $heliosDoNotVerifyNomFicUnicity = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="descr_mail_securise", type="string", length=256, nullable=true)
     */
    private $descrMailSecurise;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="helios_use_passtrans", type="boolean", nullable=true)
     */
    private $heliosUsePasstrans = false;

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
     * @var AuthorityTypes
     *
     * @ORM\ManyToOne(targetEntity="AuthorityTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_type_id", referencedColumnName="id")
     * })
     */
    private $authorityType;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
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

    public function getExtSiret(): ?string
    {
        return $this->extSiret;
    }

    public function setExtSiret(?string $extSiret): static
    {
        $this->extSiret = $extSiret;

        return $this;
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

    public function getAgreement(): ?string
    {
        return $this->agreement;
    }

    public function setAgreement(?string $agreement): static
    {
        $this->agreement = $agreement;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

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

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): static
    {
        $this->fax = $fax;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): static
    {
        $this->district = $district;

        return $this;
    }

    public function getBroadcastEmail(): ?string
    {
        return $this->broadcastEmail;
    }

    public function setBroadcastEmail(?string $broadcastEmail): static
    {
        $this->broadcastEmail = $broadcastEmail;

        return $this;
    }

    public function getEmailMailSecurise(): ?string
    {
        return $this->emailMailSecurise;
    }

    public function setEmailMailSecurise(?string $emailMailSecurise): static
    {
        $this->emailMailSecurise = $emailMailSecurise;

        return $this;
    }

    public function getDefaultBroadcastEmail(): ?string
    {
        return $this->defaultBroadcastEmail;
    }

    public function setDefaultBroadcastEmail(?string $defaultBroadcastEmail): static
    {
        $this->defaultBroadcastEmail = $defaultBroadcastEmail;

        return $this;
    }

    public function getHeliosFtpPassword(): ?string
    {
        return $this->heliosFtpPassword;
    }

    public function setHeliosFtpPassword(?string $heliosFtpPassword): static
    {
        $this->heliosFtpPassword = $heliosFtpPassword;

        return $this;
    }

    public function getHeliosFtpLogin(): ?string
    {
        return $this->heliosFtpLogin;
    }

    public function setHeliosFtpLogin(?string $heliosFtpLogin): static
    {
        $this->heliosFtpLogin = $heliosFtpLogin;

        return $this;
    }

    public function getHeliosFtpDest(): ?string
    {
        return $this->heliosFtpDest;
    }

    public function setHeliosFtpDest(?string $heliosFtpDest): static
    {
        $this->heliosFtpDest = $heliosFtpDest;

        return $this;
    }

    public function getSaeWsdl(): ?string
    {
        return $this->saeWsdl;
    }

    public function setSaeWsdl(?string $saeWsdl): static
    {
        $this->saeWsdl = $saeWsdl;

        return $this;
    }

    public function getSaeLogin(): ?string
    {
        return $this->saeLogin;
    }

    public function setSaeLogin(?string $saeLogin): static
    {
        $this->saeLogin = $saeLogin;

        return $this;
    }

    public function getSaePassword(): ?string
    {
        return $this->saePassword;
    }

    public function setSaePassword(?string $saePassword): static
    {
        $this->saePassword = $saePassword;

        return $this;
    }

    public function getSaeIdVersant(): ?string
    {
        return $this->saeIdVersant;
    }

    public function setSaeIdVersant(?string $saeIdVersant): static
    {
        $this->saeIdVersant = $saeIdVersant;

        return $this;
    }

    public function getSaeIdArchive(): ?string
    {
        return $this->saeIdArchive;
    }

    public function setSaeIdArchive(?string $saeIdArchive): static
    {
        $this->saeIdArchive = $saeIdArchive;

        return $this;
    }

    public function getSaeNumeroAggrement(): ?string
    {
        return $this->saeNumeroAggrement;
    }

    public function setSaeNumeroAggrement(?string $saeNumeroAggrement): static
    {
        $this->saeNumeroAggrement = $saeNumeroAggrement;

        return $this;
    }

    public function getSaeOriginatingAgency(): ?string
    {
        return $this->saeOriginatingAgency;
    }

    public function setSaeOriginatingAgency(?string $saeOriginatingAgency): static
    {
        $this->saeOriginatingAgency = $saeOriginatingAgency;

        return $this;
    }

    public function isNewNotification(): ?bool
    {
        return $this->newNotification;
    }

    public function setNewNotification(?bool $newNotification): static
    {
        $this->newNotification = $newNotification;

        return $this;
    }

    public function getPastellUrl(): ?string
    {
        return $this->pastellUrl;
    }

    public function setPastellUrl(?string $pastellUrl): static
    {
        $this->pastellUrl = $pastellUrl;

        return $this;
    }

    public function getPastellLogin(): ?string
    {
        return $this->pastellLogin;
    }

    public function setPastellLogin(?string $pastellLogin): static
    {
        $this->pastellLogin = $pastellLogin;

        return $this;
    }

    public function getPastellPassword(): ?string
    {
        return $this->pastellPassword;
    }

    public function setPastellPassword(?string $pastellPassword): static
    {
        $this->pastellPassword = $pastellPassword;

        return $this;
    }

    public function getPastellIdE(): ?int
    {
        return $this->pastellIdE;
    }

    public function setPastellIdE(?int $pastellIdE): static
    {
        $this->pastellIdE = $pastellIdE;

        return $this;
    }

    public function isHeliosDoNotVerifyNomFicUnicity(): ?bool
    {
        return $this->heliosDoNotVerifyNomFicUnicity;
    }

    public function setHeliosDoNotVerifyNomFicUnicity(?bool $heliosDoNotVerifyNomFicUnicity): static
    {
        $this->heliosDoNotVerifyNomFicUnicity = $heliosDoNotVerifyNomFicUnicity;

        return $this;
    }

    public function getDescrMailSecurise(): ?string
    {
        return $this->descrMailSecurise;
    }

    public function setDescrMailSecurise(?string $descrMailSecurise): static
    {
        $this->descrMailSecurise = $descrMailSecurise;

        return $this;
    }

    public function isHeliosUsePasstrans(): ?bool
    {
        return $this->heliosUsePasstrans;
    }

    public function setHeliosUsePasstrans(?bool $heliosUsePasstrans): static
    {
        $this->heliosUsePasstrans = $heliosUsePasstrans;

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

    public function getAuthorityType(): ?AuthorityTypes
    {
        return $this->authorityType;
    }

    public function setAuthorityType(?AuthorityTypes $authorityType): static
    {
        $this->authorityType = $authorityType;

        return $this;
    }


}
