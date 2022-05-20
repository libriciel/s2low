<?php

namespace S2low\Services\ProcessCommand;

use Symfony\Component\Process\Process;

class ExtractCertificateSNCommand extends CommandLauncher
{
    public function extract(string $path) : string
    {
        return $this->launch(["openssl","x509","-noout","-serial","-in",$path]);
    }

    public function getCommandOutput(Process $process, array $resultatAnalysisOptions) : AnalysedOutput
    {
        if (!$process->isSuccessful() || !preg_match("#serial=(.*)#",$process->getOutput(),$serialNumberMatches)) {
            return new AnalysedOutput("",["Impossible d'extraire le SN du certificat"]);
        }
        preg_match("#serial=(.*)#",$process->getOutput(),$serialNumberMatches);
        return new AnalysedOutput($serialNumberMatches[1]);
    }
}