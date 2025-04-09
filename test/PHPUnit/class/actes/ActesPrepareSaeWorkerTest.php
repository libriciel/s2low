<?php

use S2lowLegacy\Class\actes\ActesPrepareSaeWorker;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Model\PastellProperties;
use S2lowLegacy\Model\PastellPropertiesSQL;

class ActesPrepareSaeWorkerTest extends S2lowTestCase
{
    private const FAKE_PASTELL_URL = "http://fakePastell/";

    /**
     * @throws Exception
     */
    public function testgetAllId()
    {

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $actesPrepareSaeWorker = static::getContainer()->get(ActesPrepareSaeWorker::class);

        $this->assertEquals([$transaction_id], $actesPrepareSaeWorker->getAllId());


        $actesPrepareSaeWorker->work($transaction_id);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $wf_info = $actesTransactionSQL->getLastTransactionWorkflowInfo($transaction_id);

        $this->assertEquals(ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE, $wf_info['status_id']);


        $this->assertEquals(ActesPrepareSaeWorker::QUEUE_NAME, $actesPrepareSaeWorker->getQueueName());
        $this->assertEquals($transaction_id, $actesPrepareSaeWorker->getData($transaction_id));
    }


    /**
     * @param $status
     * @return array|bool|mixed
     * @throws Exception
     */
    private function createTransaction($status)
    {
        $sql = "INSERT INTO actes_envelopes(user_id) VALUES(1) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql);


        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,sae_transfer_identifier,type) VALUES (?,?,?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql, $envelope_id, $status, 1, 1, 42, 1);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $wd_id = $actesTransactionSQL->updateStatus($transaction_id, ActesStatusSQL::STATUS_ACQUITTEMENT_RECU, "test");

        $this->getSQLQuery()->query("UPDATE actes_transactions_workflow SET date=? WHERE id=?", "2011-02-18", $wd_id);


        $pastellPropertiesSQL = $this->getObjectInstancier()->get(PastellPropertiesSQL::class);

        $pastellProperties = new PastellProperties();
        $pastellProperties->url = self::FAKE_PASTELL_URL;
        $pastellProperties->actes_send_auto = true;
        $pastellProperties->id_e = 12;

        $pastellPropertiesSQL->editProperties(1, $pastellProperties);

        return $transaction_id;
    }
}
