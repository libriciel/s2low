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
    private const USER_COLUMNS = 'id, email, login, password, role, authority_id, authority_group_id, status, certificate_hash, name, givenname';

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
        $usersData = $this->userSQL->query(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND status = 1 ORDER BY id',
            $certificateHash
        );

        return array_map(fn($data) => new SecurityUser($data), $usersData);
    }

    /**
     * @return array<SecurityUser>
     */
    public function loadUsersByCertificateHashAndRgs2(string $certificateHash, string $certificateRgs2Etoiles): array
    {
        if (empty($certificateRgs2Etoiles)) {
            // Si pas de RGS**, chercher avec NULL ou chaîne vide
            $usersData = $this->userSQL->query(
                'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND (certificate_rgs_2_etoiles IS NULL OR certificate_rgs_2_etoiles = \'\') AND status = 1 ORDER BY id',
                $certificateHash
            );
        } else {
            // Si RGS** présent, chercher exactement
            $usersData = $this->userSQL->query(
                'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND certificate_rgs_2_etoiles = ? AND status = 1 ORDER BY id',
                [$certificateHash, $certificateRgs2Etoiles]
            );
        }

        return array_map(fn($data) => new SecurityUser($data), $usersData);
    }

    /**
     * @return array<SecurityUser>
     */
    public function loadUserByCertificateAndLogin(
        string $certificateHash,
        string $certificateRgs2Etoiles,
        string $login
    ): array {
        if (empty($certificateRgs2Etoiles)) {
            // Si pas de RGS**, chercher avec NULL ou chaîne vide
            $usersData = $this->userSQL->query(
                'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND (certificate_rgs_2_etoiles IS NULL OR certificate_rgs_2_etoiles = \'\') AND login = ? AND status = 1 ORDER BY id',
                [$certificateHash, $login]
            );
        } else {
            // Si RGS** présent, chercher exactement
            $usersData = $this->userSQL->query(
                'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND certificate_rgs_2_etoiles = ? AND login = ? AND status = 1 ORDER BY id',
                [$certificateHash, $certificateRgs2Etoiles, $login]
            );
        }

        return array_map(fn($data) => new SecurityUser($data), $usersData);
    }

    public function loadUserByCertificateAndAuthority(string $certificateHash, int $authorityId): ?SecurityUser
    {
        $userData = $this->userSQL->queryOne(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND authority_id = ? AND status = 1 ORDER BY id LIMIT 1',
            [$certificateHash, $authorityId]
        );

        if (!$userData) {
            return null;
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
        $result = $this->userSQL->queryOne(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE id = ?',
            $id
        );

        return $result ?: null;
    }
}
