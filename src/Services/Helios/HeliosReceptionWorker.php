<?php

declare(strict_types=1);

namespace S2low\Services\Helios;

use Exception;
use S2low\Services\Helios\DGFiPConnection\FTPFileRetrieveException;
use S2lowLegacy\Class\helios\HeliosAnalyseFichierRecuWorker;
use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SigTermHandler;

class HeliosReceptionWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-reception-fichier';

    private WorkerScript $workerScript;
    private S2lowLogger $s2lowLogger;
    private FTPHeliosReceiver $ftpFileGetter;
    private bool $usePasstrans;

    public function __construct(
        S2lowLogger $s2lowLogger,
        WorkerScript $workerScript,
        FTPHeliosReceiver $FTPHeliosReceiver,
        bool $usePasstrans = false
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->workerScript = $workerScript;
        $this->ftpFileGetter = $FTPHeliosReceiver;
        $this->usePasstrans = $usePasstrans;
    }

    public function getQueueName()
    {
        return self::QUEUE_NAME . ($this->usePasstrans ? '-passtrans' : '');
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
            $this->s2lowLogger->info("Début de la récupération [{$this->getQueueName()}]");
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
        try {
            $this->ftpFileGetter->recupOneFile($data);
            if ($this->workerScript) {
                $this->workerScript->putJobByClassName(HeliosAnalyseFichierRecuWorker::class, $data);
            }
        } catch (FTPFileRetrieveException $e) {
            // Dans ce cas, on va continuer à traiter les autres fichiers
            // Car la RecoverableException reste dans la boucle de traitement du CustomizableWorkerRunner
            $this->s2lowLogger->info("Probleme lors de la recuperation du fichier $data : " . $e->getMessage());
            throw new RecoverableException($e->getMessage());
        } catch (Exception $e) {
            // Dans ce cas, on sort de la boucle de traitement du CustomizableWorkerRunner
            // car tous les autres types d'Exceptions ne sont pas catchées
            $this->s2lowLogger->info("Probleme lors de la recuperation du fichier $data : " . $e->getMessage());
            throw $e;
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
