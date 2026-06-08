<?php

namespace S2low\ProcessingResults;

use Psr\Log\LoggerInterface;
use S2low\Exceptions\PesEntrantSavingException;
use S2low\Infrastructure\Directory;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

class ChangeTransactionStatus
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        #[Autowire(service: 'app.heliosResponseDirectory')]
        private readonly Directory $responseDirectory,
        private readonly Database $database,
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * @throws \S2low\Exceptions\PesEntrantSavingException
     */
    public function execute(int $transactionId, int $statusId, string $message, SplFileObject $fileObject): void
    {
        try {
            $this->database->begin();
            $this->logger->info($message);
            $this->heliosTransactionsSQL->updateStatus($transactionId, $statusId, $message);
            $this->heliosTransactionsSQL->setAcquitFilename($transactionId, $fileObject->getBasename());
            $this->filesystem->rename(
                $fileObject->getPathname(),
                $this->responseDirectory->getPath($fileObject->getBasename())
            );
            $this->database->commit();
        } catch (Throwable $e) {
            $this->database->rollback();
            throw new PesEntrantSavingException(
                sprintf(
                    '[%s] %s',
                    get_class($e),
                    $e->getMessage()
                )
            );
        }
    }
}
