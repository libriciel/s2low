<?php

namespace S2lowLegacy\Class\helios;

use S2low\Enum\HeliosStatus;

class ApiHeliosStatusResolver
{
    public static function getStatus(bool|string $PESAcquitPath, HeliosStatus $status): HeliosStatus
    {
        $statusToReturn = $status;

        if (self::PESAcquitEstIntrouvable($PESAcquitPath) && self::PESAcquitAEteRecu($status)) {
            $statusToReturn = HeliosStatus::TRANSMIS;
        }

        return $statusToReturn;
    }

    private static function PESAcquitAEteRecu(HeliosStatus $statusATester): bool
    {
        $statusSiPESAcquitRecu = [
            HeliosStatus::ACQUITTE,
            HeliosStatus::REFUSE,
            HeliosStatus::INFORMATION_DISPONIBLE
        ];

        return in_array($statusATester, $statusSiPESAcquitRecu);
    }

    private static function PESAcquitEstIntrouvable(bool|string $PESAcquitPath): bool
    {
        return false === $PESAcquitPath;
    }
}
