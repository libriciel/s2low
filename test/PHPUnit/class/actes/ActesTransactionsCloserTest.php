<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\ActesUtilitiesTestTrait;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsCloser;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class ActesTransactionsCloserTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    /**
     * @throws Exception
     */
    public function testCloseAll()
    {

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
        $transaction_id_2 = $this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);

        $sql = "UPDATE actes_transactions_workflow set date=? WHERE transaction_id=? AND status_id=?";
        $this->getSQLQuery()->query($sql, "1970-01-01", $transaction_id, ActesStatusSQL::STATUS_TRANSMIS);

        $actesTransactionsCloser = $this->getObjectInstancier()->get(ActesTransactionsCloser::class);
        $actesTransactionsCloser->closeAll();

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $status_info = $actesTransactionsSQL->getLastStatusInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $status_info['status_id']);
        $this->assertEquals("Fermeture automatique de la transaction de plus de 30 jours", $status_info['message']);

        $status_info = $actesTransactionsSQL->getLastStatusInfo($transaction_id_2);
        $this->assertEquals(ActesStatusSQL::STATUS_TRANSMIS, $status_info['status_id']);
    }
}
