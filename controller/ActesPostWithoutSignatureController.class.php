<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Class\actes\ActesAntivirusWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\RedirectException;

class ActesPostWithoutSignatureController extends Controller
{
    /**
     * @throws RedirectException
     */
    public function postAction()
    {
        $this->verifUser();

        $rgsConnexion = $this->getObjectInstancier()->get(RgsConnexion::class);

        if (! $rgsConnexion->isRgsConnexion()) {
            $this->redirect(
                "/modules/actes",
                "La télétransmission nécessite un certificat RGS<br/>Erreur : {$rgsConnexion->getLastMessage()}"
            );
        }

        $transaction_id = $this->getRecuperateurPost()->getInt('id');
        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $transaction_info = $actesTransactionSQL->getInfo($transaction_id);
        if (! $transaction_info) {
            $this->redirect(
                "/modules/actes/",
                "Aucun identifiant de transaction trouvé"
            );
        }

        $serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);

        if ($this->me->getId() != $transaction_info['user_id'] && ! $serviceUser->areCollegues($this->me->getId(), $transaction_info['user_id'])) {
            $this->redirect(
                "/modules/actes/actes_transac_show.php?id=$transaction_id",
                "Vous n'avez pas le droit de faire cela"
            );
        }

        $message = "La transaction $transaction_id a été posté sans signature";
        $workerQueueName = ActesAntivirusWorker::QUEUE_NAME;


        $actesTransactionSQL->updateStatus($transaction_id, ActesStatusSQL::STATUS_POSTE, $message);

        /** @var WorkerScript $workerScript */
        $workerScript = $this->getObjectInstancier()->get(WorkerScript::class);
        $workerScript->putJobByQueueName($workerQueueName, $transaction_id);

        $this->redirect("/modules/actes/actes_transac_show.php?id=$transaction_id", $message);
    }
}
