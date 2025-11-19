<?php

namespace S2low\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Bridge entre le système d'authentification Symfony et le code legacy.
 *
 * Ce service permet au code legacy d'accéder à l'utilisateur connecté
 * via Symfony Security sans modifier profondément la base de code existante.
 */
class LegacyAuthenticationBridge
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Retourne l'ID de l'utilisateur connecté via Symfony Security.
     *
     * @return int|null L'ID de l'utilisateur ou null si non connecté
     */
    public function getAuthenticatedUserId(): ?int
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof SecurityUser) {
            return null;
        }

        return $user->getId();
    }

    /**
     * Retourne l'utilisateur Symfony connecté.
     *
     * @return SecurityUser|null
     */
    public function getAuthenticatedUser(): ?SecurityUser
    {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof SecurityUser) {
            return null;
        }

        return $user;
    }

    /**
     * Vérifie si un utilisateur est authentifié.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->getAuthenticatedUserId() !== null;
    }
}
