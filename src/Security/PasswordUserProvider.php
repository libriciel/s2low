<?php

namespace S2low\Security;

use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * UserProvider pour l'authentification par login/password
 *
 * @implements UserProviderInterface<SecurityUser>
 */
class PasswordUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private const USER_COLUMNS = 'id, email, login, password, role, authority_id, authority_group_id, status, certificate_hash, name, givenname';

    public function __construct(
        private readonly UserSQL $userSQL
    ) {
    }

    /**
     * Charge un utilisateur par son login
     *
     * @param string $identifier Le login de l'utilisateur
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userData = $this->userSQL->queryOne(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE login = ? AND status = 1 LIMIT 1',
            $identifier
        );

        if (!$userData) {
            throw new UserNotFoundException(sprintf('Utilisateur avec le login "%s" introuvable.', $identifier));
        }

        return new SecurityUser($userData);
    }

    /**
     * Charge un utilisateur par login ET authority (pour les nonces)
     */
    public function loadUserByLoginAndAuthority(string $login, int $authorityId): ?SecurityUser
    {
        $userData = $this->userSQL->queryOne(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE login = ? AND authority_id = ? AND status = 1 LIMIT 1',
            [$login, $authorityId]
        );

        if (!$userData) {
            return null;
        }

        return new SecurityUser($userData);
    }

    /**
     * Rafraîchit les données d'un utilisateur depuis la base de données
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $userData = $this->fetchUserById($user->getId());

        if (!$userData) {
            throw new UserNotFoundException(sprintf('Utilisateur avec ID "%d" introuvable.', $user->getId()));
        }

        return new SecurityUser($userData);
    }

    /**
     * Vérifie si ce provider supporte la classe d'utilisateur donnée
     */
    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }

    /**
     * Met à jour le hash du mot de passe d'un utilisateur (si l'algorithme change)
     */
    public function upgradePassword(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof SecurityUser) {
            return;
        }

        // Mettre à jour le hash dans la base de données
        $this->userSQL->query(
            'UPDATE users SET password = ? WHERE id = ?',
            [$newHashedPassword, $user->getId()]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchUserById(int $id): ?array
    {
        $result = $this->userSQL->queryOne(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE id = ?',
            $id
        );

        return $result ?: null;
    }
}
