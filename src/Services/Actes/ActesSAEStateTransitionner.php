<?php

namespace S2low\Services\Actes;

use ActesTransaction;
use Exception;
use OpenStack\ObjectStore\v1\Models\Account;
use S2low\DTO\SAEStateTransitionRequest;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class ActesSAEStateTransitionner
{
    private const ALLOWED_OUTPUT_STATUS = [
        ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
        ActesStatusSQL::STATUS_ARCHIVE_PAR_LE_SAE,
        ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
        ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE,
        ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE
    ];
    private const ALLOWED_INPUT_STATUS = [
        ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
        ActesStatusSQL::STATUS_VALIDE,
        ActesStatusSQL::STATUS_ENVOYE_AU_SAE
    ];
    private ActesTransactionsSQL $actesTransactionsSQL;

    public function __construct(ActesTransactionsSQL $actesTransactionsSQL)
    {
        $this->actesTransactionsSQL = $actesTransactionsSQL;
    }

    public function do(SAEStateTransitionRequest $request, ?int $userAuthorityId)
    {
        $trans = new ActesTransaction();
        $trans->setId($request->transaction_id);
        if (! $trans->init()) {
            throw new Exception(sprintf(
                'Transaction %s non existante',
                $request->transaction_id
            ));
        }
        if ($trans->get('authority_id') !== $userAuthorityId) {
            throw new Exception('Mauvaise collectivite');
        }
        if (! in_array($trans->get('last_status_id'), self::ALLOWED_INPUT_STATUS, true)) {
            throw new Exception(sprintf(
                'Transition depuis le statut %s impossible',
                $trans->get('last_status_id')
            ));
        }
        if (!in_array($request->status_id, self::ALLOWED_OUTPUT_STATUS, true)) {
            throw new Exception(sprintf(
                'Transition vers le statut %s impossible',
                $request->status_id
            ));
        }
        $this->actesTransactionsSQL->updateStatus(
            $request->transaction_id,
            $request->status_id,
            'Modification par l\'API manage_actes_sae_status'
        );
    }
}
