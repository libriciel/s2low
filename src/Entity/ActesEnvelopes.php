<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActesEnvelopes
 *
 * @ORM\Table(name="actes_envelopes", uniqueConstraints={@ORM\UniqueConstraint(name="ae_id_is_in_cloud", columns={"is_in_cloud", "not_available", "id"}), @ORM\UniqueConstraint(name="actes_envelopes_submission_date_id", columns={"submission_date", "id"})})
 * @ORM\Entity
 */
class ActesEnvelopes
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_envelopes_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="submission_date", type="datetimetz", nullable=true)
     */
    private $submissionDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=128, nullable=true)
     */
    private $siren;

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
     * @var int|null
     *
     * @ORM\Column(name="authority_type_code", type="integer", nullable=true)
     */
    private $authorityTypeCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="telephone", type="string", length=25, nullable=true)
     */
    private $telephone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="file_path", type="string", length=1024, nullable=true)
     */
    private $filePath;

    /**
     * @var int|null
     *
     * @ORM\Column(name="file_size", type="integer", nullable=true)
     */
    private $fileSize;

    /**
     * @var string|null
     *
     * @ORM\Column(name="return_mail", type="string", length=1024, nullable=true)
     */
    private $returnMail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="warning_sent", type="string", length=1, nullable=true, options={"fixed"=true})
     */
    private $warningSent;

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


}
