<?php

namespace S2lowLegacy\Class\actes;

use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2lowLegacy\Class\IWorker;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ActesStoreEnveloppeWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-store-enveloppe';
    private ActesCloudStorage $cloudStorage;

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    /**
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function __construct(
        ActesCloudStorage $actesCloudStorage,
        #[Autowire(service: 'app.store.file.acte_enveloppe')]
        private readonly CloudFileStorageInterface $cloudStoreActeEnveloppe,
        private readonly LoggerInterface $logger
    ) {
        $this->cloudStorage = $actesCloudStorage;
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
        $this->logger->debug("Preparation de la sauvegarde dans le cloud de l'enveloppe acte : [$data].");

        $this->cloudStoreActeEnveloppe->storeFileOnCloud($data);

        $this->logger->info("Enveloppe acte [$data] enregistré avec succès.");
    }

    public function getMutexName($data): string
    {
        return sprintf('%s-%s', self::QUEUE_NAME, $data);
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
