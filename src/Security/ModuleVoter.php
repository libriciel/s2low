<?php

namespace S2low\Security;

use S2low\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les permissions par module
 * Gère les droits de visualisation et modification des modules
 */
class ModuleVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const GRANT = 'grant';

    /**
     * Détermine si ce voter supporte l'attribut et le sujet
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Le sujet doit être une chaîne (nom du module)
        if (!is_string($subject)) {
            return false;
        }

        // On supporte VIEW, EDIT et GRANT
        return in_array($attribute, [self::VIEW, self::EDIT, self::GRANT]);
    }

    /**
     * Vote sur l'autorisation
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être authentifié
        if (!$user instanceof User) {
            return false;
        }

        $legacyUser = $user->getLegacyUser();

        // Vérifier que l'utilisateur est actif
        if (!$legacyUser->isActive()) {
            return false;
        }

        // SADM et GADM ont tous les droits sur tous les modules
        if ($legacyUser->isGroupAdminOrSuper()) {
            return true;
        }

        // Le sujet est le nom du module
        $moduleName = $subject;

        // Vérifier les permissions selon l'attribut demandé
        return match($attribute) {
            self::VIEW => $this->canView($legacyUser, $moduleName),
            self::EDIT => $this->canEdit($legacyUser, $moduleName),
            self::GRANT => $this->canGrant($legacyUser, $moduleName),
            default => false
        };
    }

    /**
     * Vérifie si l'utilisateur peut visualiser le module
     */
    private function canView(\S2lowLegacy\Class\User $legacyUser, string $moduleName): bool
    {
        return $legacyUser->canAccess($moduleName);
    }

    /**
     * Vérifie si l'utilisateur peut modifier le module
     */
    private function canEdit(\S2lowLegacy\Class\User $legacyUser, string $moduleName): bool
    {
        return $legacyUser->canEdit($moduleName);
    }

    /**
     * Vérifie si l'utilisateur peut concéder des droits sur le module
     */
    private function canGrant(\S2lowLegacy\Class\User $legacyUser, string $moduleName): bool
    {
        // La permission GRANT nécessite d'être au moins administrateur
        if (!$legacyUser->isAdmin()) {
            return false;
        }

        // Vérifier que l'utilisateur a la permission GRANT sur ce module
        $perm = $legacyUser->getPerm($moduleName);
        return $perm === 'GRANT' || $perm === 'RW';
    }
}
