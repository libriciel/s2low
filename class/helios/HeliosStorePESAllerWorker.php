<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Cloud\CloudStorage;
use S2lowLegacy\Cloud\CloudStorageFactory;
use S2lowLegacy\Class\IWorker;
use Exception;
use S2lowLegacy\Cloud\PESAllerCloudStorage;

class HeliosStorePESAllerWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-store-pes-aller';
    private CloudStorage $cloudStorage;

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    /**
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function __construct(CloudStorageFactory $cloudStorageFactory)
    {
        $this->cloudStorage = $cloudStorageFactory->getInstanceByClassName(PESAllerCloudStorage::class);
    }

    public function getData($id): int
    {
        return $id;
    }

    public function getAllId(): array
    {
        return $this->cloudStorage->getAllObjectIdToStore();
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data): void
    {
        $this->cloudStorage->storeObject($data);
    }

    public function getMutexName($data): string
    {
        return sprintf("%s-%s", self::QUEUE_NAME, $data);
    }

    public function isDataValid($data): bool
    {
        return true;
    }

    /**
     * @return void
     */
    public function start()
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end()
    {
        // TODO: Implement end() method.
    }
}
