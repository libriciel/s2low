<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HeliosRetour
 *
 * @ORM\Table(name="helios_retour", uniqueConstraints={@ORM\UniqueConstraint(name="helios_retour_filename_id", columns={"filename", "id"})}, indexes={@ORM\Index(name="hr_id_is_incloud", columns={"is_in_cloud", "not_available", "id"}), @ORM\Index(name="IDX_B47C4C3681EC865B", columns={"authority_id"})})
 * @ORM\Entity
 */
class HeliosRetour
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="helios_retour_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siren", type="string", length=255, nullable=true)
     */
    private $siren;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date", type="datetimetz", nullable=true)
     */
    private $date;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="siret", type="string", length=14, nullable=true, options={"fixed"=true})
     */
    private $siret;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sha1", type="string", length=256, nullable=true)
     */
    private $sha1;

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
     * @var int|null
     *
     * @ORM\Column(name="file_size", type="integer", nullable=true)
     */
    private $fileSize;

    /**
     * @var Authorities
     *
     * @ORM\ManyToOne(targetEntity="Authorities")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_id", referencedColumnName="id")
     * })
     */
    private $authority;


}
