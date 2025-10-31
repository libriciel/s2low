<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use HeliosUtilitiesTestTrait;
use S2low\Services\CloudFileStorageInterface;
use S2lowLegacy\Class\helios\HeliosStorePESAllerWorker;
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

    public function testWork()
    {
        $storageMock = $this->getMockBuilder(CloudFileStorageInterface::class)
            ->disableOriginalConstructor()->getMock();

        $storageMock->expects(static::once())->method('storeFileOnCloud')->with(6587);

        $pesAllerCloudStorage = self::createMock(PESAllerCloudStorage::class);

        $heliosStorePESAllerWorker = new HeliosStorePESAllerWorker(
            $storageMock,
            $pesAllerCloudStorage,
            $this->logger
        );

        $heliosStorePESAllerWorker->work(6587);
    }
}
