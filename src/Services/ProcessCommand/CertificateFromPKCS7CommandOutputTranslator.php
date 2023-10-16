<?php

namespace S2low\Services\ProcessCommand;

use Exception;
use Symfony\Component\Process\Process;

class CertificateFromPKCS7CommandOutputTranslator implements ICommandOutputTranslator
{
    public function getCommandOutput(Process $process): AnalysedOutput
    {
        if (! $process->isSuccessful()) {
            throw new Exception("Erreur d'extraction du certificat : echec de la commande " .
                var_export($process->getCommandLine(), true));
        }

        $cert = implode("\n", [$process->getOutput()]);
        $cert .= "\n";

        return new AnalysedOutput($cert);
    }
}
