<?php

declare(strict_types=1);

namespace S2lowLegacy\Class;

class JobFetcherFromDB implements JobFetchingStrategy
{
    public function getAllData(IWorker $worker, S2lowLogger $s2lowLogger): iterable
    {
        $id_list = $worker->getAllId();
        $s2lowLogger->info(count($id_list) . ' travaux trouvées');
        foreach ($id_list as $id) {
            yield $worker->getData($id);
        }
    }

    public function init(IWorker $worker, S2lowLogger $s2lowLogger): void
    {
    }
}
