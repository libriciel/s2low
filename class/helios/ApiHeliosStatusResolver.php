<?php

namespace S2lowLegacy\Class\helios;

use S2low\Enum\HeliosStatus;

class ApiHeliosStatusResolver
{
    public function getStatus(bool|string $PESAcquitPath, HeliosStatus $status): HeliosStatus
    {
        $statusToReturn = $status;

        if ($this->PESAcquitEstIntrouvable($PESAcquitPath) && $this->PESAquitAEteRecu($status)) {
            $statusToReturn = HeliosStatus::TRANSMIS;
        }

        return $statusToReturn;
    }

    private function PESAquitAEteRecu(HeliosStatus $statusAverifier): bool
    {
        $statusSiPESAcquitRecu = [
            HeliosStatus::ACQUITTE,
            HeliosStatus::REFUSE,
            HeliosStatus::INFORMATION_DISPONIBLE
        ];

        return in_array($statusAverifier, $statusSiPESAcquitRecu);
    }

    private function PESAcquitEstIntrouvable(bool|string $PESAcquitPath): bool
    {
        return false === $PESAcquitPath;
    }
}
