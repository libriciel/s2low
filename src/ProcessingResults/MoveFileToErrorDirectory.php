<?php

namespace S2low\ProcessingResults;

use Psr\Log\LoggerInterface;
use S2low\Infrastructure\AdminMailer;
use S2low\Infrastructure\Directory;
use S2low\Services\FilesAndDirectoriesUtils\FileMover;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MoveFileToErrorDirectory
{
    public function __construct(
        private readonly Directory $errorDirectory,
        private readonly bool $mailAndLogOnError,
        private readonly AdminMailer $adminMailer,
        private readonly LoggerInterface $logger,
        private readonly FileMover $fileMover
    ) {
    }

    public function execute(SplFileObject $fileObject, string $errorMessage): void
    {
        if ($this->mailAndLogOnError) {
            $this->logger->error($errorMessage);
            $this->sendErrorMail($fileObject->getFilename(), $errorMessage);
        }
        $this->fileMover->moveFileWithRename($fileObject, $this->errorDirectory);
    }

    /**
     * @param string $fileName
     * @param string $errorMessage
     * @return void
     */
    private function sendErrorMail(string $fileName, string $errorMessage): void
    {
        $subject = '[S2low][Helios] Un fichier est en erreur sur le script de récupération des fichier PES_Acquit/PES_Retour';
        $msg = "Fichier : $fileName => $errorMessage\n";
        $msg .= "\n\nLe fichier en erreur est disponible dans le répertoire Helios Response Error\n";
        $this->adminMailer->sendMailToAdmin($subject, $msg);
    }
}
