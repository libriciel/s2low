<?php

namespace S2low\Enum;

enum UserRole: string
{
    case Utilisateur = 'USER';
    case Archiviste = 'ARCH';
    case AdministrateurCollectivite = 'ADM';
    case AdministrateurGroupe = 'GADM';
    case SuperAdministrateur = 'SADM';
}
