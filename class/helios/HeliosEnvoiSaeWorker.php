<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\IWorker;
use Exception;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosEnvoiSaeWorker implements IWorker
{
    private const MAX_TRANSACTION_TO_SEND = 100;

    public const QUEUE_NAME = 'helios-envoi-sae';

    private $heliosArchiveControler;
    private $heliosTransactionsSQL;

    public function __construct(
        HeliosEnvoiSAE $heliosArchiveControler,
        HeliosTransactionsSQL $heliosTransactionsSQL
    ) {
        $this->heliosArchiveControler = $heliosArchiveControler;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
    }

    public function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }

    public function getData($id): mixed
    {
        return $id;
    }

    /**
     * On envoie que les 100 premiers id car sinon, il est possible que le script de récup sur le cloud plante (suite à l'expiration du ticket)
     *
     * @return 0|array|int[]
     */
    public function getAllId(): array
    {
        return array_slice(
            $this->heliosTransactionsSQL->getTransactionsToSendToSAE(),
            0,
            self::MAX_TRANSACTION_TO_SEND
        );
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->heliosArchiveControler->sendArchive($data);
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
