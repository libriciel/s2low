<?php

declare(strict_types=1);

namespace S2low\Services\Helios;

use Exception;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SigTermHandler;

class HeliosReceptionWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-reception-fichier';

    private $workerScript;
    private $s2lowLogger;
    /** @var FTPHeliosReceiver  */
    private $ftpFileGetter;

    public function __construct(
        S2lowLogger $s2lowLogger,
        WorkerScript $workerScript,
        FTPHeliosReceiver $FTPHeliosReceiver
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->workerScript = $workerScript;
        $this->ftpFileGetter = $FTPHeliosReceiver;
    }

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    public function getData($id)
    {
        return $id;
    }

    /**
     * Renvoie une liste d'identifiant pour reconstruire une file
     * @return string[]
     */
    public function getAllId(): array
    {
        try {
            $this->s2lowLogger->info("Début de la récupération");
            return $this->ftpFileGetter->retrieveNames();
        } catch (Exception $e) {
            $this->s2lowLogger->info("Probleme lors de la recuperation des noms : " . $e->getMessage());
            exit;
        }
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $sigtermHandler = SigTermHandler::getInstance();
        try {
            $succes = $this->ftpFileGetter->recupOneFile($data);
            if ($this->workerScript) {
                $this->workerScript->putJobByClassName(HeliosAnalyseFichierRecuWorker::class, $data);
            }
            if ($sigtermHandler->isSigtermCalled()) {
                $this->ftpFileGetter->finTraitement();
            }
        } catch (Exception $e) {
            $this->s2lowLogger->info('Probleme lors de la recuperation du fichier $data : ' . $e->getMessage());
            $this->ftpFileGetter->finTraitement();
            exit;
        }
        if (!$succes) {
            $this->ftpFileGetter->finTraitement();
            exit;
        }
    }

    public function getMutexName($data)
    {
        return $this->getQueueName();
    }

    public function isDataValid($data)
    {
        return true;
    }

    /**
     * @return void
     */
    public function start()
    {
        $this->ftpFileGetter->debutTraitement();
    }

    /**
     * @return void
     */
    public function end()
    {
        $this->ftpFileGetter->finTraitement();
    }
}
