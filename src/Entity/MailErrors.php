<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailErrors
 *
 * @ORM\Table(name="mail_errors")
 * @ORM\Entity
 */
class MailErrors
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_errors_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="mail_message_emis_id", type="string", length=128, nullable=true, options={"fixed"=true})
     */
    private $mailMessageEmisId;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_registered", type="datetimetz", nullable=true)
     */
    private $dateRegistered;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message_retour", type="text", nullable=true)
     */
    private $messageRetour;


}
