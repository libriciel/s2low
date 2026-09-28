<?php

namespace S2low\Services\ProcessCommand;

use DateTime;
use DateTimeZone;
use S2lowLegacy\Class\RecoverableException;
use Symfony\Component\Process\Process;

class CheckSnInCRLCommandOutputTranslator implements ICommandOutputTranslator
{
    public function __construct(
        private readonly string $serialNumber,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @throws RecoverableException
     */
    public function getCommandOutput(Process $process): AnalysedOutput
    {
        $currentSerialNumber = null;
        foreach (preg_split('/\R/', $process->getOutput()) as $line) {
            if (preg_match('/^\s*Serial Number:\s*([0-9A-F]+)/i', $line, $matches)) {
                $currentSerialNumber = $matches[1];
                continue;
            }
            if ($currentSerialNumber !== $this->serialNumber) {
                continue;
            }
            if (preg_match('/Revocation Date:\s+(.+)/', $line, $matches)) {
                $revocationDate = DateTime::createFromFormat('M d H:i:s Y T', $matches[1], new DateTimeZone('UTC'));
                if ($revocationDate === false) {
                    throw new RecoverableException("Failed to parse date: $matches[1]");
                }
                // Révoqué uniquement si la révocation est antérieure à la date de signature
                if ($revocationDate < $this->dateTime) {
                    return new AnalysedOutput("", ["Certificat révoqué"]);
                }
                return new AnalysedOutput("");
            }
        }
        return new AnalysedOutput("");      // Pas trouvé dans le fichier => pas révoqué
    }
}
