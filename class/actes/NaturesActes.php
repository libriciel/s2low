<?php

namespace S2lowLegacy\Class\actes;

use S2low\Exceptions\BadNatureCodeException;

enum NaturesActes: int
{
    case DE = 1;    // Délibérations
    case AR = 2;    // Actes réglementaires
    case AI = 3;    // Actes individuels
    case CC = 4;    // Contrats conventions et avenants
    case BF = 5;    // Documents budgétaires et financiers
    case AU = 6;    // Autres

    public static function getFromString(?string $code): self
    {
        // Extract the suffix after "_"
        $parts = explode('_', $code);
        if (count($parts) !== 2) {
            throw new BadNatureCodeException("Invalid code format: $code");
        }

        if ($parts[0] !== '99') {
            throw new BadNatureCodeException("Invalid code format: $code");
        }

        return match ($parts[1]) {
            'DE' => self::DE,
            'AR' => self::AR,
            'AI' => self::AI,
            'CC' => self::CC,
            'BF' => self::BF,
            'AU' => self::AU,
            default => throw new BadNatureCodeException("Unknown code suffix: {$parts[1]}"),
        };
    }
}
