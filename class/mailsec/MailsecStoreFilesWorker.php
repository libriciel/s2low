<?php

namespace S2lowLegacy\Class\mailsec;

use Psr\Log\LoggerInterface;
use S2low\Services\CloudFileStorageInterface;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Lib\UnrecoverableException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MailsecStoreFilesWorker implements IWorker
{
    public const QUEUE_NAME = 'mailsec-included-file';

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function __construct(
        private readonly MailIncludedFilesCloudStorage $mailIncludedFilesCloudStorage,
        #[Autowire(service: 'app.store.file.mailsec')]
        private readonly CloudFileStorageInterface $storeMailSec,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getData($id): mixed
    {
        return $id;
    }

    /**
     * @return int[]
     * @throws UnrecoverableException
     */
    public function getAllId(): array
    {
        return $this->mailIncludedFilesCloudStorage->getAllObjectIdToStore();
    }

    public function work($data): void
    {
        $this->logger->debug("Preparation de la sauvegarde dans le cloud de le mailSec : [$data].");

        $this->storeMailSec->storeFileOnCloud($data);

        $this->logger->info("MailSec [$data] enregistré avec succès.");
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
