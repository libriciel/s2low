<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Lib\SQLQuery;

class ActesStatusSQL
{
    public const STATUS_EN_ERREUR = -1;
    public const STATUS_ANNULER = 0;

    public const STATUS_POSTE = 1;
    public const STATUS_EN_ATTENTE_DE_TRANSMISSION = 2;
    public const STATUS_TRANSMIS = 3;
    public const STATUS_ACQUITTEMENT_RECU = 4;

    public const STATUS_VALIDE = 5;

    public const STATUS_DOCUMENT_RECU = 7;
    public const STATUS_ACQUITTEMENT_ENVOYE = 8;

    public const STATUS_EN_ATTENTE_TRANMISSION_SAE = 19;

    public const STATUS_ERREUR_LORS_DE_L_ENVOI_SAE = 20;

    public const STATUS_ERREUR_SAE_DOC_INDISPONIBLES = 22;

    public const STATUS_DOCUMENT_RECU_PAS_DAR = 21;

    public const STATUS_ENVOYE_AU_SAE = 12;
    public const STATUS_ARCHIVE_PAR_LE_SAE = 13;

    public const STATUS_ERREUR_LORS_DE_L_ARCHIVAGE = 14;
    public const STATUS_EN_ATTENTE_D_ETRE_SIGNEE = 18;
    public const STATUS_EN_ATTENTE_D_ETRE_POSTE = 17;
    public const STATUS_DETRUITE = 16;

    public function __construct(private readonly SQLQuery $sqlQuery)
    {
    }

    public function getAllStatus()
    {
        $sql = "SELECT id, name FROM actes_status ORDER BY id";
        $result = $this->sqlQuery->getConnection()->fetchAllKeyValue($sql);

        return $result;
    }

    public static function getStatusLibelle($status_id)
    {
        $status_libelle_list = [
            19 => "En attente de transmission au SAE",
            20 => "Erreur lors de l'envoi au SAE",
            12 => "Envoyé au SAE",
            13 => "Archivé par le SAE",
            14 => "Erreur lors de l'archivage",
        ];

        return $status_libelle_list[$status_id] ?? $status_id;
    }
}
