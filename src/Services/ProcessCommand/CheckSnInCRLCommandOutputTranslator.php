<?php

namespace S2low\Services\ProcessCommand;

use Symfony\Component\Process\Process;

class CheckSnInCRLCommandOutputTranslator implements ICommandOutputTranslator
{
    public function __construct(string $serialNumber){
        $this->serialNumber = $serialNumber;
    }

    public function getCommandOutput(Process $process): AnalysedOutput
    {
        $preg_match = preg_match("#" . $this->serialNumber . "#", $process->getOutput());
        if($preg_match === 0){      // Pas trouvé dans le fichier => clairement pas révoqué
            return new AnalysedOutput("");
        }
        if($preg_match === 1){      // Trouvé dans le fichier CRL => clairement révoqué
            return new AnalysedOutput("",["Certificat révoqué"]);
        }
        return new AnalysedOutput("",[],["Erreur inconnue"]);
    }
}