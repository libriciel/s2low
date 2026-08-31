<?php

namespace S2low\Security\Authorization;

use S2low\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Est-ce que l'utilisateur connecté voit la convention @ctes d'une collectivité ?
 *
 * Elle est visible de qui administre Actes pour cette collectivité, et de l'administrateur
 * de la collectivité elle-même, qui garde accès à sa propre convention.
 */
final readonly class ActesConventionAccess
{
    public function __construct(
        private ModuleAdministration $moduleAdministration,
        private Security $security,
    ) {
    }

    public function isVisible(int $authorityId): bool
    {
        if ($this->moduleAdministration->isActesAdmin($authorityId)) {
            return true;
        }

        $user = $this->security->getUser();

        return $user instanceof SecurityUser
            && $user->isAuthorityAdmin()
            && $user->getAuthorityId() === $authorityId;
    }
}
