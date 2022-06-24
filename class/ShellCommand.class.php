<?php

use Symfony\Component\Process\Process;

class ShellCommand
{
    private $s2lowLogger;

    private $last_command;
    private $last_output;
    private $last_error;

    public function __construct(
        S2lowLogger $s2lowLogger
    ) {
        $this->s2lowLogger = $s2lowLogger;
    }

    public function exec(array $command)
    {
        $process = new Process($command);
        $command_line = $process->getCommandLine();
        $this->s2lowLogger->debug("Execution de la commande : $command_line");

        $ret = $process->run();
        $this->last_command = $process->getCommandLine();
        $this->last_output = $process->getOutput();
        $this->last_error = $process->getErrorOutput();

        $this->s2lowLogger->debug(
            "Résultat de l'éxecution de la commande : $command_line",
            [
                'ret' => $ret,
                'output' => $this->last_output,
                'error' => $this->last_error
            ]
        );

        return $ret;
    }

    public function getLastOutput()
    {
        return $this->last_output;
    }

    public function getLastCommand()
    {
        return $this->last_command;
    }

    public function getLastError()
    {
        return $this->last_error;
    }
}
