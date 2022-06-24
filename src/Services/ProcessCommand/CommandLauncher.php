<?php

namespace S2low\Services\ProcessCommand;

use Symfony\Component\Process\Process;

class CommandLauncher
{
    public function launch(array $commmand, ICommandOutputTranslator $outputTranslator): string
    {
        $process = new Process($commmand);
        try {
            $process->run();
        } catch (\Exception $exception) {
            throw new \RecoverableException(get_class($this) . " : " . $exception->getMessage());
        }

        $commandOutput = $outputTranslator->getCommandOutput($process);

        if ($commandOutput->hasBlockingErrors()) {
            throw new \Exception($commandOutput->getFirstBlockingErrorMessage());
        }

        if ($commandOutput->hasNonBlockingErrors()) {
            throw new \RecoverableException($commandOutput->getNonBlockingErrors());
        }
        return $commandOutput->getResult();
    }
}
