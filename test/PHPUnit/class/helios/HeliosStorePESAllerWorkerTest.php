<?php

use S2lowLegacy\Class\helios\HeliosStorePESAllerWorker;
use S2lowLegacy\Class\helios\PesAllerStorage;

class HeliosStorePESAllerWorkerTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;


    public function testGetAllId()
    {
        $transaction_id = $this->createTransaction();
        $heliosStorePESAllerWorker = $this->getObjectInstancier()->get(HeliosStorePESAllerWorker::class);
        $this->assertEquals([$transaction_id], $heliosStorePESAllerWorker->getAllId());
    }

    /**
     * @throws Exception
     */
    public function testWork()
    {
        $pesAllerStorage = $this->getMockBuilder(PesAllerStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pesAllerStorage

            ->method("storeNextFileById")
            ->willReturn(true);
        $this->getObjectInstancier()->set(PesAllerStorage::class, $pesAllerStorage);


        $transaction_id = $this->createTransaction();
        $heliosStorePESAllerWorker = $this->getObjectInstancier()->get(HeliosStorePESAllerWorker::class);

        $heliosStorePESAllerWorker->work($transaction_id);
        $this->assertTrue(true);
    }
}
