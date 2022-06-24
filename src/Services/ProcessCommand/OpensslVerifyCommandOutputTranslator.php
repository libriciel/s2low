<?php

namespace S2low\Services\ProcessCommand;

use Symfony\Component\Process\Process;

class OpensslVerifyCommandOutputTranslator implements ICommandOutputTranslator
{
    private const VALIDE = "VALIDE";

    public function __construct(array $resultatAnalysisOptions)
    {
        $this->resultatAnalysisOptions = $resultatAnalysisOptions;
    }

    public function getCommandOutput(Process $process): AnalysedOutput
    {
        $erreurs = [];
        foreach (explode(PHP_EOL, $process->getErrorOutput()) as $line) {
            if (preg_match("/error ([0123456789]+) at ([0123456789])+ depth lookup:(.*)/", $line, $matches)) {
                $erreurs[] = [
                    "errorCode" => $matches[1],
                    "depth" => $matches[2],
                    "message" => $matches[3]
                ];
            }
        }
        foreach ($erreurs as $erreur) {
            if (!in_array($erreur["errorCode"], $this->resultatAnalysisOptions)) {
                return new AnalysedOutput("", [$erreur["message"]]);
            }
        }
        return new AnalysedOutput($this::VALIDE);
    }
}
