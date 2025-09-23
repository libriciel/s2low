<?php

namespace S2low\Services\ProcessCommand;

use DateTime;
use S2low\Exceptions\CrlParsingException;
use S2low\Services\Certificates\CRLReader;
use Symfony\Component\Process\Process;

class CheckSnInCRLCommandOutputTranslator implements ICommandOutputTranslator
{
    /**
     * @var \S2low\Services\Certificates\CRLReader
     */
    private CRLReader $crlReader;

    public function __construct(private readonly string $serialNumber, private readonly DateTime $dateTime)
    {
        $this->crlReader = new CRLReader();
    }

    public function getCommandOutput(Process $process): AnalysedOutput
    {
        try {
            $crl = $this->crlReader->read($process->getOutput());
            if ($crl->isRevoked($this->serialNumber, $this->dateTime)) {
                return new AnalysedOutput('', ['Certificat révoqué']);
            }
            return new AnalysedOutput('');
        } catch (CrlParsingException $exception) {
            return new AnalysedOutput('', [], [$exception->getMessage()]);
        }
    }
}
