<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailTransaction
 *
 * @ORM\Table(name="mail_transaction", uniqueConstraints={@ORM\UniqueConstraint(name="mail_transaction_filename_id", columns={"fn_download", "id"})}, indexes={@ORM\Index(name="mt_ui", columns={"user_id"})})
 * @ORM\Entity
 */
class MailTransaction
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_transaction_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="objet", type="string", length=1024, nullable=false)
     */
    private $objet;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=2048, nullable=false)
     */
    private $message;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fn_download", type="string", length=512, nullable=true)
     */
    private $fnDownload;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=40, nullable=false)
     */
    private $status;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_envoi", type="datetimetz", nullable=true)
     */
    private $dateEnvoi;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=64, nullable=true)
     */
    private $password;

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
