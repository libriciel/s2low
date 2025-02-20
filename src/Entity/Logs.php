<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

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


}
