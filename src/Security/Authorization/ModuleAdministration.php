<?php

namespace S2low\Security\Authorization;

use S2low\Enum\AdministeredModule;
use S2low\Security\SecurityUser;
use S2lowLegacy\Model\AuthoritySQL;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Est-ce que l'utilisateur connecté administre un module pour une collectivité ?
 *
 * Le super administrateur administre tout. L'administrateur de groupe administre le module
 * si la collectivité a désigné son groupe, ou si elle n'existe pas encore : il est alors en
 * train de la créer et c'est son groupe qui sera désigné. Les autres rôles sont exclus.
 */
final readonly class ModuleAdministration
{
    public function __construct(
        private AuthoritySQL $authoritySQL,
        private Security $security,
    ) {
    }

    public function isActesAdmin(int $authorityId): bool
    {
        return $this->administers(AdministeredModule::ACTES, $authorityId);
    }

    public function isHeliosAdmin(int $authorityId): bool
    {
        return $this->administers(AdministeredModule::HELIOS, $authorityId);
    }

    private function administers(AdministeredModule $module, int $authorityId): bool
    {
        $user = $this->security->getUser();

        if (! $user instanceof SecurityUser) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->isGroupAdmin()) {
            return false;
        }

        $authority = $this->authoritySQL->getInfo($authorityId);

        if (! $authority) {
            return true;
        }

        return (int)($authority[$module->groupColumn()] ?? 0) === $user->getAuthorityGroupId();
    }
}
