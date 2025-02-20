<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailGroupe
 *
 * @ORM\Table(name="mail_groupe", uniqueConstraints={@ORM\UniqueConstraint(name="mail_groupe_unique", columns={"authority_id", "name"})})
 * @ORM\Entity
 */
class MailGroupe
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="mail_groupe_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=true)
     */
    private $authorityId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=false)
     */
    private $name;


}
