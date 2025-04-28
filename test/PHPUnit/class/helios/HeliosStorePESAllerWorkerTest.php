<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use Exception;
use HeliosUtilitiesTestTrait;
use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\helios\HeliosStorePESAllerWorker;
use S2lowLegacy\Class\helios\PESAllerCloudStorable;
use S2lowLegacy\Class\helios\PESAllerCloudStorage;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowTestCase;

class HeliosStorePESAllerWorkerTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }
    public function testGetAllId()
    {
        $transaction_id = $this->createTransaction();
        /** @var HeliosStorePESAllerWorker $heliosStorePESAllerWorker */
        $heliosStorePESAllerWorker = $this->getObjectInstancier()->get(HeliosStorePESAllerWorker::class);
        static::assertEquals([$transaction_id], $heliosStorePESAllerWorker->getAllId());
    }

    /**
     * @throws Exception
     */
    public function testWork()
    {
        $storageMock = $this->getMockBuilder(PESAllerCloudStorage::class)
            ->disableOriginalConstructor()->getMock();

        $storageMock->expects(static::once())->method('storeObject')->with(6587);

        $heliosStorePESAllerWorker = new HeliosStorePESAllerWorker($storageMock);

        $heliosStorePESAllerWorker->work(6587);
    }
}
