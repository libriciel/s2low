<?php

namespace S2low\Security;

use PDO;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SecurityUserProvider implements UserProviderInterface
{
    private const USER_COLUMNS = 'id, email, login, password, role, authority_id, authority_group_id, status, certificate_hash, name, givenname';

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userData = $this->fetchUserById((int) $identifier);

        if (!$userData) {
            throw new UserNotFoundException(sprintf('User with ID "%s" not found.', $identifier));
        }

        return new SecurityUser($userData);
    }

    public function loadUserByCertificateHash(string $certificateHash): ?SecurityUser
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND status = 1 ORDER BY id'
        );
        $stmt->execute([$certificateHash]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            return null;
        }

        return new SecurityUser($userData);
    }

    public function loadUsersByCertificateHash(string $certificateHash): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND status = 1 ORDER BY id'
        );
        $stmt->execute([$certificateHash]);
        $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($data) => new SecurityUser($data), $usersData);
    }

    public function loadUsersByCertificateHashAndRgs2(string $certificateHash, string $certificateRgs2Etoiles): array
    {
        if (empty($certificateRgs2Etoiles)) {
            // Si pas de RGS**, chercher avec NULL ou chaîne vide
            $stmt = $this->pdo->prepare(
                'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND (certificate_rgs_2_etoiles IS NULL OR certificate_rgs_2_etoiles = \'\') AND status = 1 ORDER BY id'
            );
            $stmt->execute([$certificateHash]);
        } else {
            // Si RGS** présent, chercher exactement
            $stmt = $this->pdo->prepare(
                'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND certificate_rgs_2_etoiles = ? AND status = 1 ORDER BY id'
            );
            $stmt->execute([$certificateHash, $certificateRgs2Etoiles]);
        }
        $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($data) => new SecurityUser($data), $usersData);
    }

    public function loadUserByCertificateAndLogin(
        string $certificateHash,
        string $certificateRgs2Etoiles,
        string $login
    ): array {
        if (empty($certificateRgs2Etoiles)) {
            // Si pas de RGS**, chercher avec NULL ou chaîne vide
            $stmt = $this->pdo->prepare(
                'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND (certificate_rgs_2_etoiles IS NULL OR certificate_rgs_2_etoiles = \'\') AND login = ? AND status = 1 ORDER BY id'
            );
            $stmt->execute([$certificateHash, $login]);
        } else {
            // Si RGS** présent, chercher exactement
            $stmt = $this->pdo->prepare(
                'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND certificate_rgs_2_etoiles = ? AND login = ? AND status = 1 ORDER BY id'
            );
            $stmt->execute([$certificateHash, $certificateRgs2Etoiles, $login]);
        }
        $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($data) => new SecurityUser($data), $usersData);
    }

    public function loadUserByCertificateAndAuthority(string $certificateHash, int $authorityId): ?SecurityUser
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE certificate_hash = ? AND authority_id = ? AND status = 1 ORDER BY id LIMIT 1'
        );
        $stmt->execute([$certificateHash, $authorityId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

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

    private function fetchUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::USER_COLUMNS . ' FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}
