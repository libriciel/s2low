<?php

namespace S2low\Security;

use DateTimeImmutable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private int $id;
    private string $email;
    private ?string $login;
    private ?string $password;
    private string $role;
    private int $authorityId;
    private ?int $authorityGroupId;
    private int $status;
    private string $certificateHash;
    private string $name;
    private string $givenname;
    private \DateTimeImmutable $certExpirationDate;
    private bool $sharedCertificate;

    /**
     * @param array<string, mixed> $userData
     */
    public function __construct(array $userData)
    {
        $this->id = (int) $userData['id'];
        $this->email = $userData['email'] ?? '';
        $this->login = $userData['login'] ?: null;
        $this->password = $userData['password'] ?: null;
        $this->role = $userData['role'] ?? '';
        $this->authorityId = (int) ($userData['authority_id'] ?? 0);
        $this->authorityGroupId = isset($userData['authority_group_id']) ? (int) $userData['authority_group_id'] : null;
        $this->status = (int) ($userData['status'] ?? 0);
        $this->certificateHash = $userData['certificate_hash'] ?? '';
        $this->sharedCertificate = $userData['login'] != null;
        try {
            $certDate = $userData['cert_not_after'] ?: '9999-12-31 23:59:59';
            $this->certExpirationDate = new DateTimeImmutable($certDate);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Impossible de lire la date d'expiration du certificat : " . $e->getMessage());
        }

        $this->name = $userData['name'] ?? '';
        $this->givenname = $userData['givenname'] ?? '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return ['ROLE_' . $this->role];
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getRoleEnum(): ?\S2low\Enum\UserRole
    {
        return \S2low\Enum\UserRole::fromRole($this->role);
    }

    public function isSuperAdmin(): bool
    {
        return $this->getRoleEnum()?->isSuperAdmin() ?? false;
    }

    public function isGroupAdmin(): bool
    {
        return $this->getRoleEnum()?->isGroupAdmin() ?? false;
    }

    public function isAuthorityAdmin(): bool
    {
        return $this->getRoleEnum()?->isAuthorityAdmin() ?? false;
    }

    public function isArchivist(): bool
    {
        return $this->getRoleEnum()?->isArchivist() ?? false;
    }

    public function isAnyAdmin(): bool
    {
        return $this->getRoleEnum()?->isAnyAdmin() ?? false;
    }

    public function isGroupOrSuperAdmin(): bool
    {
        return $this->getRoleEnum()?->isGroupOrSuperAdmin() ?? false;
    }

    public function eraseCredentials(): void
    {
        // Pas de credentials sensibles à effacer en mémoire
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLegacyRole(): string
    {
        return $this->role;
    }

    public function getAuthorityId(): int
    {
        return $this->authorityId;
    }

    public function getAuthorityGroupId(): ?int
    {
        return $this->authorityGroupId;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getCertificateHash(): string
    {
        return $this->certificateHash;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGivenname(): string
    {
        return $this->givenname;
    }

    public function getCertExpirationDate(): DateTimeImmutable
    {
        return $this->certExpirationDate;
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }

    public function isSharedCertificate(): bool
    {
        return $this->sharedCertificate;
    }
}
