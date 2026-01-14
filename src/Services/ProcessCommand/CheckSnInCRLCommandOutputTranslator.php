<?php

namespace S2low\Services\ProcessCommand;

use DateTime;
use S2low\Exceptions\CrlParsingException;
use S2low\Services\Certificates\CRLReader;
use Symfony\Component\Process\Process;

class CheckSnInCRLCommandOutputTranslator implements ICommandOutputTranslator
{
    public function __construct(
        private readonly string $serialNumber,
        private readonly DateTime $dateTime,
        private readonly CRLReader $crlReader
    ) {
    }

    public function getCommandOutput(Process $process): AnalysedOutput
    {
        try {
            $crl = $this->crlReader->read($process->getOutput());
            if ($crl->revokes($this->serialNumber, $this->dateTime)) {
                return new AnalysedOutput('', ['Certificat révoqué']);
            }
            return new AnalysedOutput('');
        } catch (CrlParsingException $exception) {
            return new AnalysedOutput('', [], [$exception->getMessage()]);
        }
    }
}
