<?php

namespace S2low\Services\Helios;

use Exception;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosEnvoiWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-envoi';
    public const PHEANSTALK_TTR = 3600;         // Si la vitesse de transfert est lente, un flux peut prendre bcp
                                                // de temps à envoyer ... On veut que le job reste reserved
                                                // Le risque est que le traitement du tube soit bloqué, mais comme on a
                                                // plusieurs Worker, ça devrait être ok.
                                                // Idéalement, on ferait un job.touch , mais je ne vois pas comment ...

    public function __construct(
        private readonly HeliosEnvoiControler $heliosEnvoiControler,
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        private readonly bool $usePasstrans,
    ) {
    }

    public function getQueueName(): string
    {
        return $this->getQueueNameParametre($this->usePasstrans);
    }

    public function getData($id): mixed
    {
        return $id;
    }

    /**
     * @return array|false|int[]
     * @throws Exception
     */
    public function getAllId(): array  //TODO : ajouter Passtrans
    {
        return $this->heliosTransactionsSQL->getIdsByStatusAndPasstrans(
            HeliosTransactionsSQL::ATTENTE,
            $this->usePasstrans
        );
    }

    /**
     * @throws Exception
     */
    public function work($data): void
    {
        $this->heliosEnvoiControler->sendOneTransaction($data, $this->usePasstrans);
    }

    public function getMutexName($data): bool|string
    {
        return sprintf("helios-transaction-%s", $data);
    }

    public function isDataValid($data): bool
    {
        $status_id = $this->heliosTransactionsSQL->getLatestStatusId($data);
        return $status_id == HeliosStatusSQL::ATTENTE;
    }

    public static function getQueueNameParametre($usePasstrans): string
    {
        return self::QUEUE_NAME . ($usePasstrans ? '-passtrans' : '');
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
