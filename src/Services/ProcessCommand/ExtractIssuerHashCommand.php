<?php

namespace S2low\Services\ProcessCommand;

use Symfony\Component\Process\Process;

class ExtractIssuerHashCommand implements ICommandOutputTranslator
{
    public function getCommandOutput(Process $process) : AnalysedOutput
    {
        if(!$process->isSuccessful()){
            return new AnalysedOutput("",["Certificat non valide : impossible d'extraire le issuer hash"]);
        }
        return new AnalysedOutput(trim ($process->getOutput()));
    }
}