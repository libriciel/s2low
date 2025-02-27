<?php

namespace S2low\Domain\Model\ValueObject;

enum StatusTransaction: int
{
    case CREE = 1;
    case ERREUR = -1;
    case ATTENTE_TRANSMISSION = 2;
    case TRANSMIS = 3;
    case RECU = 4;

    public function label(): string
    {
        return match ($this) {
            self::CREE => 'Posté',
            self::ERREUR => 'Erreur',
            self::ATTENTE_TRANSMISSION => 'En attente de transmission',
            self::TRANSMIS => 'Transmis',
            self::RECU => 'Acquittement reçu',
        };
    }
}
