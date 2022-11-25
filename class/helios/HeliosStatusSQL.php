<?php

namespace S2lowLegacy\Class\helios;

/**
 * @deprecated v4.3.12
 */
class HeliosStatusSQL
{
    public const ERREUR = -1;
    public const POSTE = 1;
    public const ATTENTE = 2;
    public const INFORMATION_DISPONIBLE = 8;
    public const STATUS_EN_ATTENTE_TRANMISSION_SAE = 19;
    public const STATUS_ERREUR_LORS_DE_L_ENVOI_SAE = 20;
    public const ENVOYER_AU_SAE = 9;
    public const ACCEPTER_PAR_LE_SAE = 10;

    public const ADETRUIRE = 21;
    public const DETRUITE = 22;



    public static function getStatusLibelle($status_id)
    {
        $status_libelle_list = [
            19 => "En attente de transmission au SAE",
            20 => "Erreur lors de l'envoi au SAE",
            9 => "Envoyé au SAE",
            10 => "Accepté par le SAE",
        ];

        return $status_libelle_list[$status_id] ?? $status_id;
    }
}
