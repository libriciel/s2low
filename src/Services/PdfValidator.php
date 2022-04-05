<?php

namespace S2low\Services;

use Exception;
use RecoverableException;
use S2lowLogger;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

class PdfValidator
{
    /** @var \S2lowLogger  */
    private $logger;

    public function __construct(S2lowLogger $logger){
        $this->logger = $logger;
    }

    /**
     * @throws \RecoverableException
     * @throws \UnexpectedValueException
     */
    public function check(string $filepath) : bool
    {
        $process = new Process(["pdfinfo",$filepath]);
        try{
            $process->run();
        } catch (Exception $exception){
            $message = "PdfValidator->check : ".$exception->getMessage();
            $this->logger->error($message);
            throw new RecoverableException();
        }
        if ($process->getExitCode() !== 0) {
            $message = "Fichier pdf corrompu : ".basename($filepath);
            $this->logger->error($message);
            throw new UnexpectedValueException($message);
        }
        return true;
    }

}