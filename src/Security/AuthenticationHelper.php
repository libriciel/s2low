<?php

namespace S2low\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Helper pour vérifier l'authentification depuis les pages legacy
 */
class AuthenticationHelper
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * Vérifie si un utilisateur est actuellement authentifié
     */
    public function isAuthenticated(): bool
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return false;
        }

        $user = $token->getUser();

        // Vérifier que l'utilisateur existe et n'est pas une chaîne (utilisateur anonyme)
        return $user && is_object($user);
    }

    /**
     * Récupère l'utilisateur actuellement authentifié
     */
    public function getUser(): ?object
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        return (is_object($user)) ? $user : null;
    }
}
