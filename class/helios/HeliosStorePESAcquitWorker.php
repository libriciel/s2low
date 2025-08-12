<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\CloudStorageException;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\UnrecoverableException;

class HeliosStorePESAcquitWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-store-pes-acquit';

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function __construct(
        private PESAcquitCloudStorage $PESAcquitCloudStorage
    ) {
    }

    public function getData($id): mixed
    {
        return $id;
    }

    /**
     * @return int[]
     */
    public function getAllId(): array
    {
        return $this->PESAcquitCloudStorage->getAllObjectIdToStore();
    }

    /**
     * @param $data
     * @return void
     * @throws CloudStorageException
     * @throws PausingQueueException
     * @throws UnrecoverableException
     */

    public function work($data)
    {
        $this->PESAcquitCloudStorage->storeObject($data);
    }

    public function isDataValid($data): bool
    {
        return true;
    }
}
