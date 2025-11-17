<?php

namespace S2low\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Entité User Symfony qui wraps la classe User legacy
 * Cette classe permet l'intégration avec le système de sécurité Symfony
 * tout en maintenant la compatibilité avec le code legacy existant
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private int $id;
    private string $email;
    private ?string $login;
    private ?string $password;
    private string $role;
    private array $permissions = [];
    private string $certificateHash;
    private int $authorityId;
    private ?int $authorityGroupId;
    private int $status;

    // Référence vers la classe legacy pour compatibilité
    private \S2lowLegacy\Class\User $legacyUser;

    /**
     * Constructeur qui wraps l'utilisateur legacy
     */
    public function __construct(\S2lowLegacy\Class\User $legacyUser)
    {
        $this->legacyUser = $legacyUser;
        $this->id = $legacyUser->get('id');
        $this->email = $legacyUser->get('email');
        $this->login = $legacyUser->get('login');
        $this->password = $legacyUser->get('password');
        $this->role = $legacyUser->get('role');
        $this->certificateHash = $legacyUser->get('certificate_hash') ?? '';
        $this->authorityId = $legacyUser->get('authority_id');
        $this->authorityGroupId = $legacyUser->get('authority_group_id');
        $this->status = $legacyUser->get('status');

        // Charger les permissions depuis l'objet legacy
        $this->loadPermissions();
    }

    /**
     * Charge les permissions depuis l'utilisateur legacy
     */
    private function loadPermissions(): void
    {
        // Les permissions sont déjà chargées via initPerms() dans l'objet legacy
        // On les récupère pour les utiliser dans getRoles()
        $perms = $this->legacyUser->get('perms');
        if (is_array($perms)) {
            $this->permissions = $perms;
        }
    }

    /**
     * Identifiant unique de l'utilisateur pour Symfony Security
     */
    public function getUserIdentifier(): string
    {
        // Utiliser le login s'il existe, sinon l'email
        return $this->login ?: $this->email;
    }

    /**
     * Retourne les rôles Symfony de l'utilisateur
     * Conversion des rôles legacy + permissions par module
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        // Mapping des rôles legacy vers Symfony
        switch ($this->role) {
            case 'SADM':
                $roles[] = 'ROLE_SADM';
                break;
            case 'GADM':
                $roles[] = 'ROLE_GADM';
                break;
            case 'ADM':
                $roles[] = 'ROLE_ADM';
                break;
            case 'ARCH':
                $roles[] = 'ROLE_ARCH';
                break;
        }

        // Ajouter permissions par module comme rôles
        foreach ($this->permissions as $moduleName => $perm) {
            if (isset($perm['perm'])) {
                if ($perm['perm'] === 'RW') {
                    $roles[] = 'ROLE_MODULE_' . strtoupper($moduleName) . '_WRITE';
                } elseif ($perm['perm'] === 'RO') {
                    $roles[] = 'ROLE_MODULE_' . strtoupper($moduleName) . '_READ';
                } elseif ($perm['perm'] === 'GRANT') {
                    $roles[] = 'ROLE_MODULE_' . strtoupper($moduleName) . '_GRANT';
                }
            }
        }

        return array_unique($roles);
    }

    /**
     * Hash du mot de passe pour Symfony Security
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Méthode appelée après authentification pour effacer les données sensibles temporaires
     */
    public function eraseCredentials(): void
    {
        // Rien à effacer car on ne stocke pas de password en clair temporaire
    }

    /**
     * Récupère l'objet User legacy pour compatibilité avec le code existant
     */
    public function getLegacyUser(): \S2lowLegacy\Class\User
    {
        return $this->legacyUser;
    }

    /**
     * Getters pour accéder aux propriétés
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCertificateHash(): string
    {
        return $this->certificateHash;
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

    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
