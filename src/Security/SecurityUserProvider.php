<?php

namespace S2low\Security;

use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
class SecurityUserProvider implements UserProviderInterface
{
    private UserSQL $userSQL;

    public function __construct(UserSQL $userSQL)
    {
        $this->userSQL = $userSQL;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userData = $this->fetchUserById((int) $identifier);

        if (!$userData) {
            throw new UserNotFoundException(sprintf('User with ID "%s" not found.', $identifier));
        }

        return new SecurityUser($userData);
    }

    /**
     * @return array<SecurityUser>
     */
    public function loadUsersByCertificateHash(string $certificateHash): array
    {
        $usersData = $this->userSQL->getUserFromCertificatHash(
            $certificateHash
        );

        return array_map(fn($data) => new SecurityUser($data), $usersData);
    }

    /**
     * @param string $certificateHash
     * @param string $login
     * @return SecurityUser|null
     */
    public function loadUserByCertificateAndLogin(
        string $certificateHash,
        string $login
    ): ?SecurityUser {
        $userData = $this->userSQL->getUserByCertificatsAndLogin($certificateHash, $login);

        return $userData ? new SecurityUser($userData) : null;
    }

    public function loadUserByCertificateAndAuthority(string $certificateHash, int $authorityId): ?SecurityUser
    {
        $userData = $this->userSQL->getUserByCertificatAndAuthority(
            $certificateHash,
            $authorityId
        );

        if (!$userData) {
            throw new UserNotFoundException('Impossible de trouver l\'utilisateur associé aux informations renseignés.');
        }

        return new SecurityUser($userData);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $userData = $this->fetchUserById($user->getId());

        if (!$userData) {
            throw new UserNotFoundException(sprintf('User with ID "%d" not found.', $user->getId()));
        }

        return new SecurityUser($userData);
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchUserById(int $id): ?array
    {
        $result = $this->userSQL->getUserById(
            $id
        );

        return $result ?: null;
    }
}
