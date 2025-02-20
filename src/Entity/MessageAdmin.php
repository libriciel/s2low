<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MessageAdmin
 *
 * @ORM\Table(name="message_admin", indexes={@ORM\Index(name="IDX_77A8F29DA76ED395", columns={"user_id"}), @ORM\Index(name="IDX_77A8F29D736D8C89", columns={"user_id_publieur"}), @ORM\Index(name="IDX_77A8F29DCCE1A952", columns={"user_id_retireur"})})
 * @ORM\Entity
 */
class MessageAdmin
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="message_admin_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="titre", type="text", nullable=false)
     */
    private $titre;

    /**
     * @var int|null
     *
     * @ORM\Column(name="niveau", type="integer", nullable=true, options={"default"="1"})
     */
    private $niveau = 1;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_publication", type="datetimetz", nullable=true)
     */
    private $datePublication;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_retrait", type="datetimetz", nullable=true)
     */
    private $dateRetrait;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=false)
     */
    private $message;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_publie", type="boolean", nullable=true)
     */
    private $isPublie = false;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="is_retire", type="boolean", nullable=true)
     */
    private $isRetire = false;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id_publieur", referencedColumnName="id")
     * })
     */
    private $userIdPublieur;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id_retireur", referencedColumnName="id")
     * })
     */
    private $userIdRetireur;


}
