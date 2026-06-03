<?php

namespace S2low\Twig\Extensions;

use S2low\Enum\UserRole;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RoleResolverExtension extends AbstractExtension
{
    private string $userRole;

    public function __construct(
        #[CurrentUser]
        Security $security
    ) {
        $this->userRole = $security->getUser() != null ? $security->getUser()->getRoles()[0] : '';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isSadm', [$this, 'isSadm']),
            new TwigFunction('isAdm', [$this, 'isAdm']),
            new TwigFunction('isGadm', [$this, 'isGadm']),
            new TwigFunction('isLargeAdmin', [$this, 'isLargeAdmin']),
            new TwigFunction('isSadmOrGadm', [$this, 'isSadmOrGadm']),
            new TwigFunction('isNonAdminUser', [$this, 'isNonAdminUser']),
            new TwigFunction('isArchivist', [$this, 'isArchivist']),
        ];
    }

    public function isSadm(): bool
    {
        return UserRole::fromRole($this->userRole) === UserRole::SuperAdministrateur;
    }

    public function isAdm(): bool
    {
        return UserRole::fromRole($this->userRole) === UserRole::AdministrateurCollectivite;
    }

    public function isGadm(): bool
    {
        return UserRole::fromRole($this->userRole) === UserRole::AdministrateurGroupe;
    }

    public function isLargeAdmin(): bool
    {
        return $this->isAdm() || $this->isSadm() || $this->isGadm();
    }

    public function isSadmOrGadm(): bool
    {
        return $this->isSadm() || $this->isGadm();
    }

    public function isNonAdminUser(): bool
    {
        return !$this->isLargeAdmin();
    }

    public function isArchivist(): bool
    {
        return UserRole::fromRole($this->userRole) === UserRole::Archiviste;
    }
}
