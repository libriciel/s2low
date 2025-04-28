<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\IWorker;
use Exception;
use S2lowLegacy\Lib\UnrecoverableException;

class HeliosMenageWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-menage';
    private const NB_DAYS_IN_DISK = 15;
    private CloudStorageFactory $cloudStorageFactory;
    private ?CloudStorage $cloudStorage = null;
    public function __construct(
        CloudStorageFactory $cloudStorageFactory,
        private PESAllerCloudStorable $PESAllerCloudStorage
    ) {
        $this->cloudStorageFactory = $cloudStorageFactory;
    }

    /**
     * @throws UnrecoverableException
     */
    private function getCloudStorage(): CloudStorage
    {
        if (is_null($this->cloudStorage)) {
            $this->cloudStorage = $this->cloudStorageFactory
                ->getInstance($this->PESAllerCloudStorage);
        }
        return $this->cloudStorage;
    }

    public function getQueueName(): string
    {
        return sprintf('%s-%s', self::QUEUE_NAME, gethostname());
    }

    public function getData($id)
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
        $this->getCloudStorage()->deleteFilesOnDisk(self::NB_DAYS_IN_DISK);
    }

    public function getMutexName($data): string
    {
        return $this->getQueueName();
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
