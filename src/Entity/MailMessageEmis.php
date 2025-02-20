<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailMessageEmis
 *
 * @ORM\Table(name="mail_message_emis", indexes={@ORM\Index(name="mail_message_emis_mail_transaction_id", columns={"mail_transaction_id"})})
 * @ORM\Entity
 */
class MailMessageEmis
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=128, nullable=false, options={"fixed"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_message_emis_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="mail_transaction_id", type="integer", nullable=false)
     */
    private $mailTransactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=256, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="type_envoi", type="string", length=64, nullable=false)
     */
    private $typeEnvoi;

    /**
     * @var bool
     *
     * @ORM\Column(name="ack", type="boolean", nullable=false)
     */
    private $ack = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="ack_date", type="datetimetz", nullable=true)
     */
    private $ackDate;


}
