<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use Exception;
use HeliosUtilitiesTestTrait;
use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\helios\HeliosStorePESAllerWorker;
use S2lowTestCase;

class HeliosStorePESAllerWorkerTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;


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
        $storageMock = $this->getMockBuilder(CloudStorage::class)
            ->disableOriginalConstructor()->getMock();

        $cloudStorageFactoryMock = $this->getMockBuilder(CloudStorageFactory::class)
            ->disableOriginalConstructor()->getMock();

        $cloudStorageFactoryMock->expects(static::once())->method('getInstanceByClassName')
            ->willReturn($storageMock);

        $storageMock->expects(static::once())->method('storeObject')->with(6587);

        $heliosStorePESAllerWorker = new HeliosStorePESAllerWorker($cloudStorageFactoryMock);

        $heliosStorePESAllerWorker->work(6587);
    }
}
