<?php

declare(strict_types=1);

namespace S2low\Services\FilesAndDirectoriesUtils;

use SplFileInfo;

/**
 * Classe qui contient les utilitaires nécessaires pour générer les noms de fichier
 * libres pour éviter d'écraser les fichiers existants dans un répertoire contenant
 * déjà des fichiers
 */
class FileNamesHandler
{
    /**
     * Add a suffix to a filename
     * ex "file.ext" becomes "file(0).ext"
     * @param string $filename
     * @param string|null $suffix
     * @return string
     */
    public function addSuffix(string $filename, ?string $suffix): string
    {
        if (is_null($suffix)) {
            return $filename;
        }
        $path_parts = pathinfo($filename);
        $extension = isset($path_parts['extension']) ? '.' . $path_parts['extension'] : '';
        return $path_parts['filename'] . "$suffix$extension";
    }

    /**
     * @param int $attempt
     * @return string|null
     */
    public function getSuffix(int $attempt): ?string
    {
        if ($attempt == 0) {
            return null;
        }
        $suffixNumber = $attempt - 1;
        return "($suffixNumber)";
    }

    /**
     * Tests if a file $filename exists in a directory $dirPath
     * @param string $outputDir
     * @param string $filename
     * @return bool
     */
    public function isAvailableFilename(string $outputDir, string $filename): bool
    {
        return !file_exists("$outputDir/$filename");
    }

    /**
     * @param string $filePath1
     * @param string $filePath2
     * @return bool
     */
    public function areSameFilesStr(string $filePath1, string $filePath2): bool
    {
        $sha1_file = sha1_file($filePath1);
        return $sha1_file == sha1_file("$filePath2");
    }

    /**
     * @param \SplFileInfo $file1
     * @param \SplFileInfo $file2
     * @return bool
     */
    public function areSameFiles(SplFileInfo $file1, SplFileInfo $file2): bool
    {
        return $this->areSameFilesStr($file1->getPathname(), $file2->getPathname());
    }
}
