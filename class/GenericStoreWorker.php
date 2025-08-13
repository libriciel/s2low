<?php

namespace S2lowLegacy\Class;

use Exception;

class GenericStoreWorker implements IWorker
{
    public function __construct(
        private readonly CloudStorage $cloudStorage,
        private readonly string $queueName
    ) {
    }
    public function getQueueName(): string
    {
        return $this->queueName;
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

    public function isDataValid($data): bool
    {
        return true;
    }
}
