<?php

namespace S2lowLegacy\Class\helios;

class HeliosSAEDateManager
{
    private mixed $helios_retention_fichiers_nb_jours;

    public function __construct($helios_retention_fichiers_nb_jours)
    {
        $this->helios_retention_fichiers_nb_jours = $helios_retention_fichiers_nb_jours;
    }

    public function getDateBeforeWhichWeDestroy(): string
    {
        $timestamp = strtotime(sprintf("-%d days", $this->helios_retention_fichiers_nb_jours));

        if ($timestamp < strtotime("1970-01-01")) {
            throw new \Exception(
                sprintf("Impossible de supprimer les transactions plus anciennes que  $this->helios_retention_fichiers_nb_jours jours")
            );
        }
        return date("Y-m-d", $timestamp);
    }
}
