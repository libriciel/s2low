<?php

namespace S2lowLegacy\Class\helios;

use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2lowLegacy\Class\IWorker;
use Exception;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
        #[Autowire(service: 'app.store.file.pes_aller')]
        private readonly CloudFileStorageInterface $cloudStorePesAller,
        private readonly PESAllerCloudStorage $PESAllerCloudStorage,
        private readonly HeliosTransactionsSQL $repository,
        private readonly LoggerInterface $logger
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
        $this->logger->debug("Preparation de la sauvegarde dans le cloud du PesAller : [$data].");

        $this->cloudStorePesAller->storeFileOnCloud($data);

        $this->logger->info("PesAller [$data] enregistré avec succès.");
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
