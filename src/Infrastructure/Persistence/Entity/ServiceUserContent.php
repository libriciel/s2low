<?php

namespace S2low\Infrastructure\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;
use S2low\Infrastructure\Persistence\Entity\ServiceUser;
use S2low\Infrastructure\Persistence\Entity\Users;

/**
 * ServiceUserContent
 *
 * @ORM\Table(name="service_user_content", uniqueConstraints={@ORM\UniqueConstraint(name="service_user_content_unique", columns={"id_user", "id_service"})}, indexes={@ORM\Index(name="IDX_79645B463F0033A2", columns={"id_service"}), @ORM\Index(name="IDX_79645B466B3CA4B", columns={"id_user"})})
 * @ORM\Entity
 */
class ServiceUserContent
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="service_user_content_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var ServiceUser
     *
     * @ORM\ManyToOne(targetEntity="ServiceUser")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_service", referencedColumnName="id")
     * })
     */
    private $idService;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     * })
     */
    private $idUser;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdService(): ?ServiceUser
    {
        return $this->idService;
    }

    public function setIdService(?ServiceUser $idService): static
    {
        $this->idService = $idService;

        return $this;
    }

    public function getIdUser(): ?Users
    {
        return $this->idUser;
    }

    public function setIdUser(?Users $idUser): static
    {
        $this->idUser = $idUser;

        return $this;
    }


}
