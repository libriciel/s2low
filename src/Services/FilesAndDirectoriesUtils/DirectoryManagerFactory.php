<?php

declare(strict_types=1);

namespace S2low\Services\FilesAndDirectoriesUtils;

/**
 * Factory for FileNamesHandler
 */
class DirectoryManagerFactory
{
    /**
     * @var FileNamesHandler
     */
    private FileNamesHandler $fileNamesHandler;

    /**
     * @param FileNamesHandler $fileNamesHandler
     */
    public function __construct(FileNamesHandler $fileNamesHandler)
    {
        $this->fileNamesHandler = $fileNamesHandler;
    }

    /**
     * @param string $dirpath Path of the directory to manage
     * @return DirectoryManager
     */
    public function get(string $dirpath): DirectoryManager
    {
        return new DirectoryManager($dirpath, $this->fileNamesHandler);
    }
}
