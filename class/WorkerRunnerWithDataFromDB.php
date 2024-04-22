<?php

namespace S2lowLegacy\Class;

class WorkerRunnerWithDataFromDB implements IWorkerRunnerStrategies
{
    public function getAllId(IWorker $worker, S2lowLogger $s2lowLogger): iterable
    {
        $id_list = $worker->getAllId();
        $s2lowLogger->info(count($id_list) . ' travaux trouvées');
        foreach ($id_list as $id) {
            yield $id;
        }
    }

    public function init(IWorker $worker, S2lowLogger $s2lowLogger): void
    {
    }
}
