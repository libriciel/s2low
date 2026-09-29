<?php

namespace S2low\Enum;

/**
 * Les modules dont l'administration est déléguée au groupe que la collectivité désigne.
 *
 * Le module Mail n'en fait pas partie : il n'a pas de groupe administrateur.
 */
enum AdministeredModule: int
{
    case ACTES = Module::ACTES->value;
    case HELIOS = Module::HELIOS->value;

    public function groupColumn(): string
    {
        return match ($this) {
            self::ACTES => 'actes_group_id',
            self::HELIOS => 'helios_group_id',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTES => 'Actes',
            self::HELIOS => 'Helios',
        };
    }
}
