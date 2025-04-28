<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Class\CloudStorageFactory;
use S2lowLegacy\Class\IWorker;
use Exception;
use S2lowLegacy\Lib\UnrecoverableException;

class ActesMenageEnveloppeWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-enveloppe-menage';
    private const NB_DAYS_IN_DISK = 15;

    private CloudStorageFactory $cloudStorageFactory;
    private ?CloudStorage $cloudStorage = null;
    private int $nb_days_in_disk;
    private ActesCloudStorable $actesCloudStorage;

    public function __construct(CloudStorageFactory $cloudStorageFactory, ActesCloudStorable $actesCloudStorage)
    {
        $this->cloudStorageFactory = $cloudStorageFactory;
        $this->actesCloudStorage = $actesCloudStorage;
        $this->setNbDayInDisk(self::NB_DAYS_IN_DISK);
    }

    /**
     * @return CloudStorage
     * @throws UnrecoverableException
     */
    private function getCloudStorage(): CloudStorage
    {
        if (is_null($this->cloudStorage)) {
            $this->cloudStorage = $this->cloudStorageFactory
                ->getInstance($this->actesCloudStorage);
        }
        return $this->cloudStorage;
    }


    public function getQueueName()
    {
        return sprintf('%s-%s', self::QUEUE_NAME, gethostname());
    }

    public function getData($id): int
    {
        return $id;
    }

    public function getAllId(): array
    {
        return [1];
    }

    public function setNbDayInDisk(int $nb_days_in_disk): void
    {
        $this->nb_days_in_disk = $nb_days_in_disk;
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data): void
    {
        $this->getCloudStorage()->deleteFilesOnDisk($this->nb_days_in_disk, true);
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
