<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HeliosTransmissionWindows
 *
 * @ORM\Table(name="helios_transmission_windows")
 * @ORM\Entity
 */
class HeliosTransmissionWindows
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="helios_transmission_windows_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="rate_limit", type="integer", nullable=true)
     */
    private $rateLimit;


}
