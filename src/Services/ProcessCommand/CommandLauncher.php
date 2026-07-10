<?php

namespace S2low\Services\ProcessCommand;

use S2lowLegacy\Class\RecoverableException;
use Exception;
use Symfony\Component\Process\Process;

class CommandLauncher
{
    /**
     * @throws RecoverableException
     */
    public function launch(array $commmand, ICommandOutputTranslator $outputTranslator): string
    {
        return $this->launchProcess(
            new Process($commmand),
            $outputTranslator
        );
    }

    /**
     * @throws RecoverableException
     */
    public function launchFromString(string $string, ICommandOutputTranslator $outputTranslator): string
    {
        return $this->launchProcess(
            Process::fromShellCommandline($string),
            $outputTranslator
        );
    }

    /**
     * @throws RecoverableException
     * @throws Exception
     */
    private function launchProcess(
        Process $process,
        ICommandOutputTranslator $outputTranslator
    ): string {
        try {
            $process->run();
        } catch (Exception $exception) {
            throw new RecoverableException(get_class($this) . ' : ' . $exception->getMessage());
        }

        $commandOutput = $outputTranslator->getCommandOutput($process);

        if ($commandOutput->hasBlockingErrors()) {
            throw new Exception($commandOutput->getFirstBlockingErrorMessage());
        }

        if ($commandOutput->hasNonBlockingErrors()) {
            throw new RecoverableException($commandOutput->getNonBlockingErrors());
        }
        return $commandOutput->getResult();
    }
}
