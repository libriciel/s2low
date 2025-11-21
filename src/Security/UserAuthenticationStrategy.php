<?php

namespace S2low\Security;

use S2lowLegacy\Class\PasswordHandler;
use S2lowLegacy\Model\NounceSQL;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class UserAuthenticationStrategy
{
    public function __construct(
        private readonly SecurityUserProvider $userProvider,
        private readonly PasswordUserProvider $passwordUserProvider,
        private readonly PasswordHandler $passwordHandler,
        private readonly NounceSQL $nounceSQL,
    ) {
    }

    public function authenticateByNonce(
        ?string $certificateHash,
        string $nonce,
        string $login,
        string $hash
    ): ?SecurityUser {
        $authorityId = $this->nounceSQL->verify($login, $nonce, $hash);

        if (!$authorityId) {
            return null;
        }

        // Si pas de certificat, chercher par login + authority
        if (empty($certificateHash)) {
            return $this->passwordUserProvider->loadUserByLoginAndAuthority($login, $authorityId);
        }

        return $this->userProvider->loadUserByCertificateAndAuthority($certificateHash, $authorityId);
    }

    public function authenticateByCertificate(
        string $certificateHash,
        string $certificateRgs2
    ): ?SecurityUser {
        $users = $this->findUsersForCertificate($certificateHash, $certificateRgs2);

        if ($this->hasNoUsers($users)) {
            throw new CustomUserMessageAuthenticationException("Le certificat n'est pas valide : aucun compte trouvé");
        }

        if ($this->hasSingleUser($users)) {
            return $users[0];
        }

        return null;
    }

    public function authenticateByCertificateAndCredentials(
        string $certificateHash,
        string $certificateRgs2,
        string $login,
        string $password
    ): SecurityUser {
        $matchingUsers = $this->findUsersForCertificateAndLogin($certificateHash, $certificateRgs2, $login);

        if ($this->hasNoUsers($matchingUsers)) {
            throw new CustomUserMessageAuthenticationException('login_incorrect');
        }

        $authenticatedUser = $this->findUserWithValidPassword($matchingUsers, $password);

        if (!$authenticatedUser) {
            throw new CustomUserMessageAuthenticationException('password_incorrect');
        }

        return $authenticatedUser;
    }

    public function countUsersForCertificate(string $certificateHash, string $certificateRgs2): int
    {
        $users = $this->findUsersForCertificate($certificateHash, $certificateRgs2);
        return count($users);
    }

    /**
     * @return array<SecurityUser>
     */
    private function findUsersForCertificate(string $certificateHash, string $certificateRgs2): array
    {
        return $this->userProvider->loadUsersByCertificateHashAndRgs2($certificateHash, $certificateRgs2);
    }

    /**
     * @return array<SecurityUser>
     */
    private function findUsersForCertificateAndLogin(string $certificateHash, string $certificateRgs2, string $login): array
    {
        return $this->userProvider->loadUserByCertificateAndLogin($certificateHash, $certificateRgs2, $login);
    }

    /**
     * @param array<SecurityUser> $users
     */
    private function findUserWithValidPassword(array $users, string $password): ?SecurityUser
    {
        foreach ($users as $user) {
            if ($this->isPasswordValidForUser($password, $user)) {
                return $user;
            }
        }

        return null;
    }

    private function isPasswordValidForUser(string $password, SecurityUser $user): bool
    {
        $storedHash = $user->getPassword();

        if (!$storedHash) {
            return false;
        }

        return $this->passwordHandler->passwordMatchesHash($password, $storedHash, $user->getId());
    }

    /**
     * @param array<SecurityUser> $users
     */
    private function hasNoUsers(array $users): bool
    {
        return empty($users);
    }

    /**
     * @param array<SecurityUser> $users
     */
    private function hasSingleUser(array $users): bool
    {
        return count($users) === 1;
    }
}
