<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2lowLegacy\Class\IWorker;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class HeliosStorePESAcquitWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-store-pes-acquit';

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function __construct(
        #[Autowire(service: 'app.store.file.pes_acquit')]
        private readonly CloudFileStorageInterface $cloudStorePesAcquit,
        private readonly PESAcquitCloudStorage $PESAcquitCloudStorage,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getData($id): mixed
    {
        return $id;
    }

    /**
     * @return int[]
     */
    public function getAllId(): array
    {
        return $this->PESAcquitCloudStorage->getAllObjectIdToStore();
    }

    public function work($data): void
    {
        $this->logger->debug("Preparation de la sauvegarde dans le cloud du PesAcquit : [$data].");

        $this->cloudStorePesAcquit->storeFileOnCloud($data);

        $this->logger->info("PesAcquit [$data] enregistré avec succès.");
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
