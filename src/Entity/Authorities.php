<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

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


}
