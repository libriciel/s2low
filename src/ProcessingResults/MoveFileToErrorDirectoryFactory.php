<?php

namespace S2low\ProcessingResults;

use HeliosRetour;
use Psr\Log\LoggerInterface;
use S2low\Infrastructure\AdminMailer;
use S2low\Infrastructure\Directory;
use S2low\Services\FilesAndDirectoriesUtils\FileMover;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

class MoveFileToErrorDirectoryFactory
{
    private bool $mailAndLogOnError = true;
    public function __construct(
        #[Autowire(service: 'app.heliosErrorDirectory')]
        private readonly Directory $errorDirectory,
        private readonly LoggerInterface $logger,
        private readonly AdminMailer $adminMailer,
        private readonly FileMover $fileMover
    ) {
    }

    public function disableMailAndLoggingOnError(): void
    {
        $this->mailAndLogOnError = false;
    }

    public function get(): MoveFileToErrorDirectory
    {
        return new MoveFileToErrorDirectory(
            $this->errorDirectory,
            $this->mailAndLogOnError,
            $this->adminMailer,
            $this->logger,
            $this->fileMover
        );
    }
}
