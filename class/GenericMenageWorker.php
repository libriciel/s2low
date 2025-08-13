<?php

namespace S2lowLegacy\Class;

use Exception;

class GenericMenageWorker implements IWorker
{
    private const NB_DAYS_IN_DISK = 15;
    public function __construct(
        private readonly CloudStorage $cloudStorage,
        private readonly string $queueName
    ) {
    }
    public function getQueueName(): string
    {
        return sprintf('%s-%s', $this->queueName, gethostname());
    }

    public function getData($id): int
    {
        return $id;
    }

    public function getAllId(): array
    {
        return [1];
    }


    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data): void
    {
        $this->cloudStorage->deleteFilesOnDisk(self::NB_DAYS_IN_DISK);
    }

    public function isDataValid($data): bool
    {
        return true;
    }
}
