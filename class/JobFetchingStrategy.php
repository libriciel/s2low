<?php

namespace S2lowLegacy\Class;

interface JobFetchingStrategy
{
    public function init(IWorker $worker, S2lowLogger $s2lowLogger): void;
    public function getAllData(IWorker $worker, S2lowLogger $s2lowLogger): iterable;
}
