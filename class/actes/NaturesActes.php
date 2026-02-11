<?php

namespace S2lowLegacy\Class\actes;

use RuntimeException;
use S2low\Exceptions\BadNatureCodeException;

enum NaturesActes: int
{
    case DE = 1;    // Délibérations
    case AR = 2;    // Actes réglementaires
    case AI = 3;    // Actes individuels
    case CC = 4;    // Contrats conventions et avenants
    case BF = 5;    // Documents budgétaires et financiers
    case AU = 6;    // Autres

    public static function getFromString(string $code): self
    {
        if (!in_array($code, self::getPossiblesNatures(), true)) {
            throw new BadNatureCodeException(
                sprintf(
                    'Code invalide : valeur parmi %s attendue, %s fourni',
                    self::getPossiblesNaturesAsString(),
                    $code
                )
            );
        }

        // Extract the suffix after "_"
        [,$suffixe_nature] = explode('_', $code, 2);

        foreach (self::cases() as $case) {
            if ($case->name === $suffixe_nature) {
                return $case;
            }
        }
        throw new RuntimeException("Impossible de traiter le code $code");
    }

    private static function getPossiblesNatures(): array
    {
        return array_map(
            fn ($case) => '99_' . $case->name,
            self::cases()
        );
    }

    private static function getPossiblesNaturesAsString(): string
    {
        return implode(', ', self::getPossiblesNatures());
    }
}
