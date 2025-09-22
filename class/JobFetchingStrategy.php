<?php

namespace S2lowLegacy\Class;

use Psr\Log\LoggerInterface;

interface JobFetchingStrategy
{
    public function init(IWorker $worker, LoggerInterface $s2lowLogger): void;
    public function getAllData(IWorker $worker, LoggerInterface $s2lowLogger): iterable;
}
