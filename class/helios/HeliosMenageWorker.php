<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\IWorker;
use Exception;

class HeliosMenageWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-menage';
    private const NB_DAYS_IN_DISK = 15;
    public function __construct(
        private readonly PESAllerCloudStorage $PESAllerCloudStorage
    ) {
    }

    public function getQueueName(): string
    {
        return sprintf('%s-%s', self::QUEUE_NAME, gethostname());
    }

    public function getData($id): mixed
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
        $this->PESAllerCloudStorage->deleteFilesOnDisk(self::NB_DAYS_IN_DISK);
    }

    public function isDataValid($data): bool
    {
        return true;
    }

    /**
     * @return void
     */
    public function start(): void
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end(): void
    {
        // TODO: Implement end() method.
    }
}
