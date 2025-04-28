<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\IWorker;
use Exception;

class HeliosStorePESAllerWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-store-pes-aller';

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    /**
     */
    public function __construct(
        private readonly PESAllerCloudStorage $PESAllerCloudStorage
    ) {
    }

    public function getData($id): int
    {
        return $id;
    }

    public function getAllId(): array
    {
        return $this->PESAllerCloudStorage->getAllObjectIdToStore();
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data): void
    {
        $this->PESAllerCloudStorage->storeObject($data);
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
