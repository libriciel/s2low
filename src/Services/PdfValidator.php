<?php

namespace S2low\Services;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\RecoverableException;
use Exception;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

class PdfValidator
{
    /** @var LoggerInterface  */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws RecoverableException
     * @throws UnexpectedValueException
     */
    public function check(string $filepath): bool
    {
        $process = new Process(["pdfinfo",$filepath]);
        try {
            $process->run();
        } catch (Exception $exception) {
            $message = "PdfValidator->check : " . $exception->getMessage();
            $this->logger->error($message);
            throw new RecoverableException();
        }
        if ($process->getExitCode() !== 0) {
            $message = "Fichier pdf corrompu : " . basename($filepath);
            $this->logger->error($message);
            throw new UnexpectedValueException($message);
        }
        return true;
    }
}
