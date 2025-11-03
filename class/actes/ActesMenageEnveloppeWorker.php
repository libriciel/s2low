<?php

namespace S2lowLegacy\Class\actes;

use Psr\Log\LoggerInterface;
use S2low\Services\RemoveStoredFilesOnDisk;
use S2lowLegacy\Class\IWorker;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ActesMenageEnveloppeWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-enveloppe-menage';
    private const NB_DAYS_IN_DISK = 15;
    private int $nb_days_in_disk;
    private ActesCloudStorage $actesCloudStorage;

    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'app.removeFiles.acte_enveloppe')]
        private readonly RemoveStoredFilesOnDisk $removeStoredFilesOnDisk,
    ) {
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
        try {
            $this->removeStoredFilesOnDisk->findAndRemoveLocalFilesAlreadyCloudSaved($this->nb_days_in_disk);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
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
