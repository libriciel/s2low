<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsCloser;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowTestCase;

class ActesTransactionsCloserTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    /**
     * @throws Exception
     */
    public function testCloseAll()
    {

        $vieilleTransactionId = $this->createTransaction(
            ActesStatusSQL::STATUS_TRANSMIS,
            '',
            '1970-01-01'
        );
        $recenteTransactionId = $this->createTransaction(
            ActesStatusSQL::STATUS_TRANSMIS,
            '',
            date('Y-m-d H:i:s')
        );

        /** @var ActesTransactionsCloser $actesTransactionsCloser */
        $actesTransactionsCloser = $this->getObjectInstancier()->get(ActesTransactionsCloser::class);
        $actesTransactionsCloser->closeAll();

        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $status_info = $actesTransactionsSQL->getLastStatusInfo($vieilleTransactionId);
        static::assertEquals(ActesStatusSQL::STATUS_EN_ERREUR, $status_info['status_id']);
        static::assertEquals('Fermeture automatique de la transaction de plus de 30 jours', $status_info['message']);

        $status_info = $actesTransactionsSQL->getLastStatusInfo($recenteTransactionId);
        static::assertEquals(ActesStatusSQL::STATUS_TRANSMIS, $status_info['status_id']);
    }
}
