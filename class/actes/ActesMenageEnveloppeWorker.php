<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\IWorker;
use Exception;

class ActesMenageEnveloppeWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-enveloppe-menage';
    private const NB_DAYS_IN_DISK = 15;
    private int $nb_days_in_disk;
    private ActesCloudStorage $actesCloudStorage;

    public function __construct(ActesCloudStorage $actesCloudStorage)
    {
        $this->actesCloudStorage = $actesCloudStorage;
        $this->setNbDayInDisk(self::NB_DAYS_IN_DISK);
    }


    public function getQueueName(): string
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
        $this->actesCloudStorage->deleteFilesOnDisk($this->nb_days_in_disk, true);
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
