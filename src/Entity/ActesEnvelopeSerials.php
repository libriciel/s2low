<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActesEnvelopeSerials
 *
 * @ORM\Table(name="actes_envelope_serials")
 * @ORM\Entity
 */
class ActesEnvelopeSerials
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_envelope_serials_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=true)
     */
    private $authorityId;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="reset_date", type="datetimetz", nullable=true)
     */
    private $resetDate;

    /**
     * @var int|null
     *
     * @ORM\Column(name="serial", type="integer", nullable=true)
     */
    private $serial;


}
