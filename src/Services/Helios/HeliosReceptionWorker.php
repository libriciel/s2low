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

    public function __construct(
        private readonly S2lowLogger $s2lowLogger,
        private readonly WorkerScript $workerScript,
        private readonly FTPHeliosReceiverManager $receiverManager,
        private bool $usePasstrans = false
    ) {
    }

    public function getQueueName(): string
    {
        return self::QUEUE_NAME . ($this->usePasstrans ? '-passtrans' : '');
    }

    public function getData($id): mixed
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
            return $this->receiverManager->get()->retrieveNames();
        } catch (Exception $e) {
            $this->s2lowLogger->info("Probleme lors de la recuperation des noms : " . $e->getMessage());
            exit;
        }
    }

    /**
     * @throws Exception
     */
    public function work($data): void
    {
        try {
            $this->receiverManager->get()->recupOneFile($data);
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

    public function isDataValid($data): bool
    {
        return true;
    }
}
