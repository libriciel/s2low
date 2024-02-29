<?php

namespace S2lowLegacy\Class\actes;
namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\IWorker;
use S2lowLegacy\Class\WorkerScript;
use Exception;

class ActesReceptionFichierWorker implements IWorker
{
    public const QUEUE_NAME = 'actes-reception-fichier';

    private $actesImapRetrieve;
    private $workerScript;

    public function __construct(
        ActesImapRetrieve $actesImapRetrieve,
        WorkerScript $workerScript
    ) {
        $this->actesImapRetrieve = $actesImapRetrieve;
        $this->workerScript = $workerScript;
    }

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    public function getData($id)
    {
        return $id;
    }

    public function getAllId()
    {
        return [1];
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->actesImapRetrieve->retrieve();
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
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end()
    {
        // TODO: Implement end() method.
    }
}
