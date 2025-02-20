<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HeliosTransactions
 *
 * @ORM\Table(name="helios_transactions", uniqueConstraints={@ORM\UniqueConstraint(name="ht_to_send_in_cloud", columns={"is_in_cloud", "not_available", "id"}), @ORM\UniqueConstraint(name="ht_pes_aquit_in_cloud", columns={"pes_acquit_is_in_cloud", "pes_acquit_not_available", "id"}), @ORM\UniqueConstraint(name="helios_transactions_sha1_id", columns={"sha1", "id"}), @ORM\UniqueConstraint(name="helios_transactions_pes_acquit_filename_id", columns={"acquit_filename", "id"}), @ORM\UniqueConstraint(name="helios_transactions_last_status_id", columns={"last_status_id", "id"})}, indexes={@ORM\Index(name="xml_nomfic_index", columns={"xml_nomfic"}), @ORM\Index(name="xml_nomfic_cod_col_index", columns={"xml_nomfic", "xml_cod_col"}), @ORM\Index(name="ht_user_id", columns={"user_id", "id"}), @ORM\Index(name="helios_transactions_sha1", columns={"sha1"}), @ORM\Index(name="helios_transactions_authority_id_last_status_id_idx", columns={"authority_id", "last_status_id"}), @ORM\Index(name="IDX_86EC75E081EC865B", columns={"authority_id"}), @ORM\Index(name="IDX_86EC75E0A76ED395", columns={"user_id"})})
 * @ORM\Entity
 */
class HeliosTransactions
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="helios_transactions_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=1024, nullable=false)
     */
    private $filename;

    /**
     * @var int|null
     *
     * @ORM\Column(name="file_size", type="integer", nullable=true)
     */
    private $fileSize;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=128, nullable=true)
     */
    private $siren;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sha1", type="string", length=40, nullable=true, options={"fixed"=true})
     */
    private $sha1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="warning_sent", type="integer", nullable=true)
     */
    private $warningSent;

    /**
     * @var string|null
     *
     * @ORM\Column(name="url_archivage", type="string", length=1024, nullable=true, options={"fixed"=true})
     */
    private $urlArchivage;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="submission_date", type="datetimetz", nullable=true)
     */
    private $submissionDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="xml_nomfic", type="string", length=255, nullable=true)
     */
    private $xmlNomfic;

    /**
     * @var string|null
     *
     * @ORM\Column(name="acquit_filename", type="string", length=255, nullable=true)
     */
    private $acquitFilename;

    /**
     * @var string|null
     *
     * @ORM\Column(name="complete_name", type="string", length=150, nullable=true)
     */
    private $completeName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_transfer_identifier", type="string", length=256, nullable=true)
     */
    private $saeTransferIdentifier;

    /**
     * @var int|null
     *
     * @ORM\Column(name="last_status_id", type="integer", nullable=true)
     */
    private $lastStatusId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="archive_url", type="string", length=1024, nullable=true)
     */
    private $archiveUrl;

    /**
     * @var bool
     *
     * @ORM\Column(name="signature_technique", type="boolean", nullable=false)
     */
    private $signatureTechnique = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="xml_cod_col", type="string", length=3, nullable=true, options={"fixed"=true})
     */
    private $xmlCodCol;

    /**
     * @var string|null
     *
     * @ORM\Column(name="xml_id_post", type="string", length=7, nullable=true)
     */
    private $xmlIdPost;

    /**
     * @var string|null
     *
     * @ORM\Column(name="xml_cod_bud", type="string", length=2, nullable=true, options={"fixed"=true})
     */
    private $xmlCodBud;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_in_cloud", type="boolean", nullable=false)
     */
    private $isInCloud = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="not_available", type="boolean", nullable=false)
     */
    private $notAvailable = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="pes_acquit_is_in_cloud", type="boolean", nullable=false)
     */
    private $pesAcquitIsInCloud = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="pes_acquit_not_available", type="boolean", nullable=false)
     */
    private $pesAcquitNotAvailable = false;

    /**
     * @var Authorities
     *
     * @ORM\ManyToOne(targetEntity="Authorities")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_id", referencedColumnName="id")
     * })
     */
    private $authority;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;


}
