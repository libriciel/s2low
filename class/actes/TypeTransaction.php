<?php

namespace S2lowLegacy\Class\actes;

use S2low\Exceptions\BadTypeTransactionCode;

/**
 * Les différents types de transaction tels que définis par le cahier des charges ACTES
 */
enum TypeTransaction: int
{
    case TransmissionActe = 1;
    case CourrierSimple = 2;
    case DemandePieceComplementaire = 3;
    case LettreDObservation = 4;
    case DefereAuTribunalAdministratif = 5;
    case Annulation = 6;
    case DemandeDeClassification = 7;
    public static function checkCode(int $transactionCode): void
    {
        if (is_null(self::tryFrom($transactionCode))) {
            throw new BadTypeTransactionCode(
                sprintf(
                    'Code %s invalide, les valeurs possibles sont : %s',
                    $transactionCode,
                    self::getPossibleCodesAsString()
                )
            );
        }
    }
    private static function getPossibleCodes(): array
    {
        return array_map(
            fn($case) => $case->name . ' : ' . $case->value,
            self::cases()
        );
    }
    private static function getPossibleCodesAsString(): string
    {
        return implode(', ', self::getPossibleCodes());
    }
}
