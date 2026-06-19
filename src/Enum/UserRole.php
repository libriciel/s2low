<?php

namespace S2low\Enum;

enum UserRole: string
{
    case Utilisateur = 'USER';
    case Archiviste = 'ARCH';
    case AdministrateurCollectivite = 'ADM';
    case AdministrateurGroupe = 'GADM';
    case SuperAdministrateur = 'SADM';

    public static function fromRole($value): ?UserRole
    {
        if (!is_string($value)) {
            return null;
        }
        if (str_starts_with($value, 'ROLE_')) {
            return self::tryFrom(substr($value, 5));
        }
        return self::tryFrom($value);
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdministrateur;
    }

    public function isGroupAdmin(): bool
    {
        return $this === self::AdministrateurGroupe;
    }

    public function isAuthorityAdmin(): bool
    {
        return $this === self::AdministrateurCollectivite;
    }

    public function isArchivist(): bool
    {
        return $this === self::Archiviste;
    }

    public function isUser(): bool
    {
        return $this === self::Utilisateur;
    }

    public function isAnyAdmin(): bool
    {
        return $this === self::SuperAdministrateur
            || $this === self::AdministrateurGroupe
            || $this === self::AdministrateurCollectivite;
    }

    public function isGroupOrSuperAdmin(): bool
    {
        return $this === self::SuperAdministrateur
            || $this === self::AdministrateurGroupe;
    }

    /**
     * Récupère le label lisible associé au rôle
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Utilisateur => 'Utilisateur',
            self::Archiviste => 'Archiviste',
            self::AdministrateurCollectivite => 'Administrateur de Collectivité',
            self::AdministrateurGroupe => 'Administrateur de Groupe',
            self::SuperAdministrateur => 'Super Administrateur',
        };
    }
}
