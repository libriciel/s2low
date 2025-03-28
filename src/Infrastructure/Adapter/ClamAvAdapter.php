<?php

namespace S2low\Infrastructure\Adapter;

use S2low\Domain\Exception\VirusDetectedException;
use S2low\Domain\Port\AntivirusFilesScannerInterface;
use Symfony\Component\Filesystem\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class ClamAvAdapter implements AntivirusFilesScannerInterface
{
    private string $clamScanBinary;

    public function __construct(string $clamScanBinary)
    {
        $this->clamScanBinary = $clamScanBinary;

        if (!file_exists($clamScanBinary) || !is_executable($clamScanBinary)) {
            throw new RuntimeException("L'exécutable ClamAV '{$clamScanBinary}' est introuvable ou non exécutable.");
        }
    }

    /**
     * @param string $filePath
     * @return bool
     * @throws \RuntimeException|VirusDetectedException
     */
    public function scan(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Le fichier '{$filePath}' est introuvable.");
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("Le fichier '{$filePath}' est illisible.");
        }

        $process = new Process([$this->clamScanBinary, $filePath]);
        $process->setTimeout(60);
        $process->run();

        if ($this->virusIsDetected($process->getExitCode())) {
            $this->handleVirusDetection($process->getOutput(), $filePath);
        }

        if ($this->isScanError($process->getExitCode())) {
            throw new RuntimeException("Erreur '{$process->getExitCodeText()}' pendant le scan Antivirus du fichier {$filePath}.");
        }

        return true;
    }

    /**
     * @param string $output
     * @param string $filePath
     * @return void
     * @throws VirusDetectedException
     */
    private function handleVirusDetection(string $output, string $filePath): void
    {
        if (str_contains($output, "FOUND")) {
            if (preg_match('/: (.+?) FOUND/', $output, $matches)) {
                $virusName = trim($matches[1]);
            } else {
                $virusName = "nom inconnu";
            }

            throw new VirusDetectedException($filePath, $virusName);
        }
    }

    /**
     * @param int $exitCode
     * @return bool
     */
    private function virusIsDetected(int $exitCode): bool
    {
        return $exitCode === 1;
    }

    /**
     * @param int|null $exitCode
     * @return bool
     */
    private function isScanError(?int $exitCode): bool
    {
        return $exitCode > 1;
    }
}
