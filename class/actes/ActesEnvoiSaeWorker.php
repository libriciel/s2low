<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\IWorker;
use Exception;

class ActesEnvoiSaeWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-envoi-sae';
    private const MAX_NUMBER_OF_SIMULTANEOUS_PENDING_ARCHIVE = 100;

    private $actesArchiveControler;

    public function __construct(
        ActesArchiveControler $actesArchiveControler
    ) {
        $this->actesArchiveControler = $actesArchiveControler;
    }

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function getData($id): mixed
    {
        return $id;
    }

    public function getAllId(): array
    {
        return $this->actesArchiveControler->getAllTransactionIdToSend(self::MAX_NUMBER_OF_SIMULTANEOUS_PENDING_ARCHIVE);
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->actesArchiveControler->sendArchive($data);
    }

    public function isDataValid($data): bool
    {
        return true;
    }

    public function start(): void
    {
        // TODO: Implement start() method.
    }

    public function end(): void
    {
        // TODO: Implement end() method.
    }
}
