<?php

namespace S2low\Enum;

enum UserRole: string
{
    case Utilisateur = 'USER';
    case Archiviste = 'ARCH';
    case AdministrateurCollectivite = 'ADM';
    case AdministrateurGroupe = 'GADM';
    case SuperAdministrateur = 'SADM';

    public static function fromRole($value): UserRole
    {
        return self::from(substr($value, 5));
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
