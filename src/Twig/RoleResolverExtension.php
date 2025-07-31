<?php

namespace S2low\Twig;

use S2low\Enum\UserRole;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RoleResolverExtension extends AbstractExtension
{
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

    public function isSadm(string $role): bool
    {
        return UserRole::from($role) === UserRole::SuperAdministrateur;
    }

    public function isAdm(string $role): bool
    {
        return UserRole::from($role) === UserRole::AdministrateurCollectivite;
    }

    public function isGadm(string $role): bool
    {
        return UserRole::from($role) === UserRole::AdministrateurGroupe;
    }

    public function isLargeAdmin(string $role): bool
    {
        return $this->isAdm($role) || $this->isSadm($role) || $this->isGadm($role);
    }

    public function isSadmOrGadm(string $role): bool
    {
        return $this->isSadm($role) || $this->isGadm($role);
    }

    public function isNonAdminUser(string $role): bool
    {
        return !$this->isLargeAdmin($role);
    }

    public function isArchivist(string $role): bool
    {
        return UserRole::from($role) === UserRole::Archiviste;
    }
}
