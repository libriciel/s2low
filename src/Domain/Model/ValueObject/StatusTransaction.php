<?php

namespace S2low\Domain\Model\ValueObject;

enum StatusTransaction: int
{
    case CREE = 1;
    case ERREUR = -1;
    case TRANSMIS = 3;

    public function label(): string
    {
        return match ($this) {
            self::CREE => 'Posté',
            self::ERREUR => 'Erreur',
            self::TRANSMIS => 'Transmis',
        };
    }
}
