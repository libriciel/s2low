<?php

namespace S2low\Services\ProcessCommand;

use Symfony\Component\Process\Process;

class ExtractIssuerHashCommand extends CommandLauncher
{
    public function extract(string $path) : string
    {
        return trim ($this->launch([OPENSSL_PATH,"x509","-noout","-issuer_hash","-in", "$path"]));
    }

    public function getCommandOutput(Process $process, array $resultatAnalysisOptions) : AnalysedOutput
    {
        if(!$process->isSuccessful()){
            return new AnalysedOutput("",["Certificat non valide : impossible d'extraire le issuer hash"]);
        }
        return new AnalysedOutput(trim ($process->getOutput()));
    }
}