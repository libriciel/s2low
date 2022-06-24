<?php

namespace S2low\Services\ProcessCommand;

use Symfony\Component\Process\Process;

class ExtractCertificateSNCommandOutputTranslator implements ICommandOutputTranslator
{
    public function getCommandOutput(Process $process): AnalysedOutput
    {
        if (!$process->isSuccessful() || !preg_match("#serial=(.*)#", $process->getOutput(), $serialNumberMatches)) {
            return new AnalysedOutput("", ["Impossible d'extraire le SN du certificat"]);
        }
        preg_match("#serial=(.*)#", $process->getOutput(), $serialNumberMatches);
        return new AnalysedOutput($serialNumberMatches[1]);
    }
}
