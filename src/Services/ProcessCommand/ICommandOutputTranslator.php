<?php

namespace S2low\Services\ProcessCommand;

use Symfony\Component\Process\Process;

interface ICommandOutputTranslator
{
    public function getCommandOutput(Process $process): AnalysedOutput;
}