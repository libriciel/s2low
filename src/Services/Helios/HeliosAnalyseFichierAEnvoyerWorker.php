<?php

namespace S2low\Services\Helios;

use Exception;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosAnalyseFichierAEnvoyerWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-analyse-fichier-a-envoyer';


    private $heliosEnvoiControler;
    private $heliosTransactionsSQL;
    private $workerScript;

    public function __construct(
        HeliosEnvoiControler $heliosEnvoiControler,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        WorkerScript $workerScript
    ) {
        $this->heliosEnvoiControler = $heliosEnvoiControler;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
    }

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function getData($id): int
    {
        return $id;
    }

    public function getAllId(): array
    {
        return $this->heliosTransactionsSQL->getIdsByStatus(HeliosTransactionsSQL::POSTE);
    }

    /**
     * @throws Exception
     */
    public function work($data): void
    {
        $this->heliosEnvoiControler->validateOneTransaction($data);
    }

    public function isDataValid($data): bool
    {
        $status_id = $this->heliosTransactionsSQL->getLatestStatusId($data);
        return $status_id == HeliosTransactionsSQL::POSTE;
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
