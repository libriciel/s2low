<?php

namespace S2lowLegacy\Class\actes;

use S2low\Enum\ActesStatus;
use S2lowLegacy\Lib\SQLQuery;

class ActesStatusSQL
{
    public const int STATUS_EN_ERREUR = ActesStatus::ERREUR->value;
    public const int STATUS_ANNULER = ActesStatus::ANNULE->value;

    public const int STATUS_POSTE = ActesStatus::POSTE->value;
    public const int STATUS_EN_ATTENTE_DE_TRANSMISSION = ActesStatus::EN_ATTENTE_DE_TRANSMISSION->value;
    public const int STATUS_TRANSMIS = ActesStatus::TRANSMIS->value;
    public const int STATUS_ACQUITTEMENT_RECU = ActesStatus::ACQUITTEMENT_RECU->value;

    public const int STATUS_VALIDE = ActesStatus::VALIDE->value;

    public const int STATUS_DOCUMENT_RECU = ActesStatus::DOCUMENT_RECU->value;
    public const int STATUS_ACQUITTEMENT_ENVOYE = ActesStatus::ACQUITTEMENT_ENVOYE->value;

    public const int STATUS_EN_ATTENTE_TRANMISSION_SAE = ActesStatus::EN_ATTENTE_DE_TRANSMISSION_AU_SAE->value;

    public const int STATUS_ERREUR_LORS_DE_L_ENVOI_SAE = ActesStatus::ERREUR_LORS_DE_L_ENVOI_AU_SAE->value;

    public const int STATUS_ERREUR_SAE_DOC_INDISPONIBLES = ActesStatus::ERREUR_SAE_DOCUMENTS_INDISPONIBLES->value;

    public const int STATUS_DOCUMENT_RECU_PAS_DAR = ActesStatus::DOCUMENT_RECU_PAS_D_AR->value;

    public const int STATUS_ENVOYE_AU_SAE = ActesStatus::ENVOYE_AU_SAE->value;
    public const int STATUS_ARCHIVE_PAR_LE_SAE = ActesStatus::ARCHIVE_PAR_LE_SAE->value;

    public const int STATUS_ERREUR_LORS_DE_L_ARCHIVAGE = ActesStatus::ERREUR_LORS_DE_L_ARCHIVAGE->value;
    public const int STATUS_EN_ATTENTE_D_ETRE_SIGNEE = ActesStatus::EN_ATTENTE_D_ETRE_SIGNEE->value;
    public const int STATUS_EN_ATTENTE_D_ETRE_POSTE = ActesStatus::EN_ATTENTE_D_ETRE_POSTEE->value;
    public const int STATUS_DETRUITE = ActesStatus::DETRUITE->value;

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
