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
    private $heliosEnvoiControler;
    private $heliosTransactionsSQL;

    public function __construct(
        HeliosEnvoiControler $heliosEnvoiControler,
        HeliosTransactionsSQL $heliosTransactionsSQL,
        bool $usePasstrans
    ) {
        $this->heliosEnvoiControler = $heliosEnvoiControler;
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->usePasstrans = $usePasstrans;
    }

    public function getQueueName()
    {
        return $this->getQueueNameParametre($this->usePasstrans);
    }

    public function getData($id)
    {
        return $id;
    }

    /**
     * @return array|false|int[]
     * @throws Exception
     */
    public function getAllId()  //TODO : ajouter Passtrans
    {
        return $this->heliosTransactionsSQL->getIdsByStatusAndPasstrans(
            HeliosTransactionsSQL::ATTENTE,
            $this->usePasstrans
        );
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->heliosEnvoiControler->sendOneTransaction($data, $this->usePasstrans);
    }

    public function getMutexName($data)
    {
        return sprintf("helios-transaction-%s", $data);
    }

    public function isDataValid($data)
    {
        $status_id = $this->heliosTransactionsSQL->getLatestStatusId($data);
        return $status_id == HeliosStatusSQL::ATTENTE;
    }

    /**
     * @return string
     */
    public static function getQueueNameParametre($usePasstrans): string
    {
        return self::QUEUE_NAME . ($usePasstrans ? '-passtrans' : '');
    }
}
