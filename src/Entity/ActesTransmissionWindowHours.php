<?php

namespace S2low\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActesTransmissionWindowHours
 *
 * @ORM\Table(name="actes_transmission_window_hours", indexes={@ORM\Index(name="IDX_F824A7C6BF88B51F", columns={"transmission_window_id"})})
 * @ORM\Entity
 */
class ActesTransmissionWindowHours
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_transmission_window_hours_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="window_begin", type="datetimetz", nullable=true)
     */
    private $windowBegin;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="window_end", type="datetimetz", nullable=true)
     */
    private $windowEnd;

    /**
     * @var int|null
     *
     * @ORM\Column(name="consumed", type="integer", nullable=true)
     */
    private $consumed;

    /**
     * @var ActesTransmissionWindows
     *
     * @ORM\ManyToOne(targetEntity="ActesTransmissionWindows")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="transmission_window_id", referencedColumnName="id")
     * })
     */
    private $transmissionWindow;


}
