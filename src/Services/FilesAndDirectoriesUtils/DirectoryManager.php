<?php

declare(strict_types=1);

namespace S2low\Services\FilesAndDirectoriesUtils;

use Exception;
use SplFileInfo;
use Throwable;

/**
 * Classe permettant de gérer un répertoire contenant des fichiers.
 * Fournit des méthodes pour copier un fichier en le renommant si nécessaire
 */
class DirectoryManager
{
    /**
     * @var FileNamesHandler
     */
    private FileNamesHandler $fileNamesHandler;
    private string $directoryName;
    private int $neededDiskFreeSpace;

    /**
     * @param string $directoryName
     * @param FileNamesHandler $fileNamesHandler
     * @param int $neededDiskFreeSpace
     */
    public function __construct(
        string $directoryName,
        FileNamesHandler $fileNamesHandler,
        int $neededDiskFreeSpace = 1000000
    ) {
        $this->directoryName = $directoryName;
        $this->fileNamesHandler = $fileNamesHandler;
        $this->neededDiskFreeSpace = $neededDiskFreeSpace;
    }

    /**
     * Moves a file to the directory
     * If a file with different content and a same filename exists, it is copied using a filename(n) or filename(n).ext
     * with n as low as possible.
     * @param SplFileInfo $originFile
     * @param string $outputFileName
     * @param int $maxAttempts
     * @return SplFileInfo
     * @throws \Exception
     */
    public function getAvailableFilenameForFile(
        SplFileInfo $originFile,
        string $outputFileName,
        int $maxAttempts = 100
    ): SplFileInfo {
        for ($attemptIndex = 0; $attemptIndex < $maxAttempts; $attemptIndex++) {
            $possibleOutputFile = $this->getPossibleOutputFile($outputFileName, $attemptIndex);
            if (! $this->sameFileNameExistsInDir($possibleOutputFile->getFilename())) {
                    return $possibleOutputFile;
            }
            if ($this->fileNamesHandler->areSameFiles($originFile, $possibleOutputFile)) {
                return $possibleOutputFile;
            }
            $attemptIndex++;
        }
        throw new Exception("Unable to find a filename for $outputFileName in " . $this->directoryName);
    }

    /**
     * @param SplFileInfo $originFile
     * @param string $outputFileName
     * @return SplFileInfo
     * @throws Exception
     */
    public function moveFileInDir(SplFileInfo $originFile, string $outputFileName): SplFileInfo
    {
        $expectedFile = new SplFileInfo($this->directoryName . '/' . $outputFileName);
        $this->moveFile($originFile, $expectedFile, true);
        return $expectedFile;
    }

    /**
     * @param SplFileInfo $originFile
     * @param string $outputFileName
     * @return SplFileInfo
     * @throws Exception
     */
    public function moveFileInDirWithRename(SplFileInfo $originFile, string $outputFileName): SplFileInfo
    {
        $expectedFile = $this->getAvailableFilenameForFile($originFile, $outputFileName);

        $this->moveFile($originFile, $expectedFile);
        return $expectedFile;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function check(): void
    {
        if (! is_dir($this->directoryName)) {
            throw new Exception('[' . $this::class . "] $this->directoryName n'est pas un répertoire");
        }
        if (disk_free_space($this->directoryName) < $this->neededDiskFreeSpace) {
            throw new Exception(
                "Il ne reste pas assez d'espace sur le disque pour créer de fichier dans $this->directoryName !"
            );
        }
    }

    /**
     * @param string $string
     * @return SplFileInfo
     */
    public function getTmpFile(string $string): SplFileInfo
    {
        return new SplFileInfo($this->directoryName . '/' . $string . mt_rand(0, mt_getrandmax()));
    }

    /**
     * @param string $outputFileName
     * @return bool
     */
    public function sameFileNameExistsInDir(string $outputFileName): bool
    {
        return !$this->fileNamesHandler->isAvailableFilename($this->directoryName, $outputFileName);
    }

    /**
     * @param SplFileInfo $originFile
     * @param string $outputFileName
     * @return bool
     */
    public function fileWithOutputNameExistsAndHasSameContent(SplFileInfo $originFile, string $outputFileName): bool
    {
        if ($this->fileNamesHandler->isAvailableFilename($this->directoryName, $outputFileName)) {
            return false;
        }
        $possibleFile = new SplFileInfo($this->directoryName . '/' . $outputFileName);
        return $this->fileNamesHandler->areSameFiles($originFile, $possibleFile);
    }

    /**
     * @param string $desiredFileName
     * @param int $attemptIndex
     * @return SplFileInfo
     */
    private function getPossibleOutputFile(string $desiredFileName, int $attemptIndex): SplFileInfo
    {
        $filename = $this->fileNamesHandler->addSuffix(
            $desiredFileName,
            $this->fileNamesHandler->getSuffix($attemptIndex)
        );

        return new SplFileInfo("$this->directoryName/$filename");
    }

    /**
     * @param SplFileInfo $originFile
     * @param SplFileInfo $expectedFile
     * @param bool $checkExpectedFile
     * @return void
     * @throws Exception
     */
    private function moveFile(
        SplFileInfo $originFile,
        SplFileInfo $expectedFile,
        bool $checkExpectedFile = false
    ): void {
        $details = '';
        if ($checkExpectedFile && file_exists($expectedFile->getPathname())) {
            throw new Exception(
                "Fichier {$expectedFile->getFilename()} déjà existant dans $this->directoryName"
            );
        }

        if (!file_exists($originFile->getPathname())) {
            throw new Exception("Le fichier {$originFile->getPathname()} n'existe pas");
        }
        try {
            $success = rename($originFile->getPathname(), $expectedFile->getPathname());
        } catch (Throwable $throwable) {
            $success = false;
            $details = '[' . $throwable::class . '] : ' . $throwable->getMessage();
        }

        if (!$success) {
            $message = "Impossible de renommer {$originFile->getPathname()} en {$expectedFile->getPathname()}";
            $message .= $details;
            throw new Exception(
                $message
            );
        }
    }
}
