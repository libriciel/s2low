<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogsRequest
 *
 * @ORM\Table(name="logs_request", indexes={@ORM\Index(name="logs_request_user_id_demandeur_idx", columns={"user_id_demandeur"}), @ORM\Index(name="logs_request_state_idx", columns={"state"}), @ORM\Index(name="IDX_4549D48A81EC865B", columns={"authority_id"}), @ORM\Index(name="IDX_4549D48ABE5846DA", columns={"authority_group_id"}), @ORM\Index(name="IDX_4549D48AA76ED395", columns={"user_id"})})
 * @ORM\Entity
 */
class LogsRequest
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="logs_request_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="state", type="integer", nullable=false)
     */
    private $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_demande", type="datetimetz", nullable=false)
     */
    private $dateDemande;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_traitement", type="datetimetz", nullable=true)
     */
    private $dateTraitement;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_debut", type="date", nullable=false)
     */
    private $dateDebut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_fin", type="date", nullable=false)
     */
    private $dateFin;

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
     * @var AuthorityGroups
     *
     * @ORM\ManyToOne(targetEntity="AuthorityGroups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="authority_group_id", referencedColumnName="id")
     * })
     */
    private $authorityGroup;

    /**
     * @var \Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id_demandeur", referencedColumnName="id")
     * })
     */
    private $userIdDemandeur;

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
