<?php

namespace S2low\Services;

use Symfony\Component\Process\Process;

class CheckSnInCRLCommand extends CommandLauncher
{

    public function check(string $crlPath, string $serialNumber) : void
    {
        $this->launch(["openssl","crl","-in",$crlPath,"-text","-noout"],[$serialNumber]);
    }

    public function getCommandOutput(Process $process, array $resultatAnalysisOptions): AnalysedOutput
    {
        $preg_match = preg_match("#" . $resultatAnalysisOptions[0] . "#", $process->getOutput());
        if($preg_match === 0){      // Pas trouvé dans le fichier => clairement pas révoqué
            return new AnalysedOutput("");
        }
        if($preg_match === 1){      // Trouvé dans le fichier CRL => clairement révoqué
            return new AnalysedOutput("",["Certificat révoqué"]);
        }
        return new AnalysedOutput("",[],["Erreur inconnue"]);
    }
}