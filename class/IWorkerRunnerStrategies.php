<?php

namespace S2lowLegacy\Class;

interface IWorkerRunnerStrategies
{
    public function init(IWorker $worker, S2lowLogger $s2lowLogger): void;
    public function getAllId(IWorker $worker, S2lowLogger $s2lowLogger): iterable;
}
