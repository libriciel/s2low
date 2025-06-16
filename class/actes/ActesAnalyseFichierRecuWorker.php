<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\IWorker;
use Exception;

class ActesAnalyseFichierRecuWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-analyse-fichier-recu';


    private $actesAnalyseFichierRecuController;

    public function __construct(
        ActesAnalyseFichierRecuController $actesAnalyseFichierRecuController
    ) {
        $this->actesAnalyseFichierRecuController = $actesAnalyseFichierRecuController;
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
     * @throws Exception
     */
    public function getAllId(): array
    {
        return $this->actesAnalyseFichierRecuController->getAllDirectory();
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->actesAnalyseFichierRecuController->analyseOneFileMoveIfError($data);
    }

    public function getMutexName($data): bool|string
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
