<?php

namespace S2low\Domain\Model\ValueObject;

enum StatusTransaction: int
{
    case ERREUR = -1;

    /**
     * Retourne un libellé lisible pour chaque statut.
     */
    public function label(): string
    {
        return match ($this) {
            self::ERREUR => 'Erreur',
        };
    }
}
