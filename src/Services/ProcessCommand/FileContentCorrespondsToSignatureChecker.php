<?php

namespace S2low\Services\ProcessCommand;

use Exception;
use Symfony\Component\Process\Process;

class FileContentCorrespondsToSignatureChecker implements ICommandOutputTranslator
{
    /**
     * @throws \Exception
     */
    public function getCommandOutput(Process $process): AnalysedOutput
    {
        if (!$process->isSuccessful()) {
            throw new Exception("La vérification de la signature a échoué(code " . $process->getExitCode() . "):  (command : " . $process->getCommandLine() . ") (retour : " . $process->getErrorOutput() . ")");
        }
        return new AnalysedOutput($process->getOutput());
    }
}
