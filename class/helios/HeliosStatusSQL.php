<?php

namespace S2lowLegacy\Class\helios;

use Exception;
use S2lowLegacy\Lib\SQLQuery;

/**
 * Pour le coup, pour être en cohérence avec Actes, ce seraient plutôt les statuts
 * contenus dans HeliosTransactionsSQL qui devraient être mis ici ...
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
    public const STATUS_ERREUR_SAE_DOC_INDISPONIBLES = 23;
    public const ENVOYER_AU_SAE = 9;
    public const ACCEPTER_PAR_LE_SAE = 10;

    public const ADETRUIRE = 21;
    public const DETRUITE = 22;


    public function __construct(private SQLQuery $sqlQuery)
    {
    }

    /**
     * @throws Exception
     */
    public function getAllStatus(): array
    {
        $sql = 'SELECT id, name FROM helios_status ORDER BY id';
        return $this->sqlQuery->query($sql);
    }



    public static function getStatusLibelle($status_id)
    {
        $status_libelle_list = [
            19 => 'En attente de transmission au SAE',
            20 => "Erreur lors de l'envoi au SAE",
            9 => 'Envoyé au SAE',
            10 => 'Accepté par le SAE',
        ];

        return $status_libelle_list[$status_id] ?? $status_id;
    }
}
