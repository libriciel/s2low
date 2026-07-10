<?php

namespace S2low\Component\Process;

use RuntimeException;
use Symfony\Component\Process\Process;

class StreamingProcessRunner
{
    public function run(
        Process $process,
        StreamingParserInterface $parser
    ): mixed {
        $buffer = '';
        $stoppedEarly = false;

        $process->start(function (
            string $type,
            string $chunk
        ) use (
            &$buffer,
            &$stoppedEarly,
            $parser,
            $process
        ) {

            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                $parser->parseLine($line);

                if ($parser->isFinished()) {
                    $stoppedEarly = true;
                    $process->stop();
                    return;
                }
            }
        });
        $process->wait();

        if (!$stoppedEarly && $buffer !== '') {
            $parser->parseLine($buffer);
        }

        if (!$stoppedEarly && !$process->isSuccessful()) {
            throw new RuntimeException(
                sprintf(
                    "Erreur lors de l\'exécution de %s\n STDIN :  %s \nSTDOUT : %s\n",
                    $process->getCommandLine(),
                    $process->getOutput(),
                    $process->getErrorOutput()
                )
            );
        }

        return $parser->getResult();
    }
}
