<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use S2low\Services\RemoveStoredFilesOnDisk;
use S2lowLegacy\Class\IWorker;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class HeliosMenageWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-menage';
    private const NB_DAYS_IN_DISK = 15;
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'app.removeFiles.pes_aller')]
        private readonly RemoveStoredFilesOnDisk $removeOldFilesOnDisk,
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
        try {
            $this->removeOldFilesOnDisk->findAndRemoveLocalFilesAlreadyCloudSaved(self::NB_DAYS_IN_DISK);
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
