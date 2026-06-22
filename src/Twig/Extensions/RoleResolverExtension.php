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
            new TwigFunction('isSadm', [$this, 'isSuperAdmin']),
            new TwigFunction('isAdm', [$this, 'isAuthorityAdmin']),
            new TwigFunction('isGadm', [$this, 'isGroupAdmin']),
            new TwigFunction('isLargeAdmin', [$this, 'isAnyAdmin']),
            new TwigFunction('isSadmOrGadm', [$this, 'isGroupOrSuperAdmin']),
        ];
    }

    public function isSuperAdmin(): bool
    {
        return UserRole::fromRole($this->userRole) === UserRole::SuperAdministrateur;
    }

    public function isAuthorityAdmin(): bool
    {
        return UserRole::fromRole($this->userRole) === UserRole::AdministrateurCollectivite;
    }

    public function isGroupAdmin(): bool
    {
        return UserRole::fromRole($this->userRole) === UserRole::AdministrateurGroupe;
    }

    public function isAnyAdmin(): bool
    {
        return $this->isAuthorityAdmin() || $this->isSuperAdmin() || $this->isGroupAdmin();
    }

    public function isGroupOrSuperAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->isGroupAdmin();
    }
}
