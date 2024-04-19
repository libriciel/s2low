<?php

namespace S2lowLegacy\Class;

use Exception;
use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\SigTermHandler;

class WorkerRunnerWithDataFromDB extends AbstractWorkerRunner
{
    protected function getAllId()
    {
        $id_list = $this->worker->getAllId();
        $this->s2lowLogger->info(count($id_list) . " travaux trouvées");

        foreach ($id_list as $id) {
            yield $id;
        }
    }
}
