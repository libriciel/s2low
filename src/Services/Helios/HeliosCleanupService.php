<?php

namespace S2low\Services\Helios;

use Psr\Log\LoggerInterface;
use S2low\Exceptions\TransactionNotFoundException;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class HeliosCleanupService
{
    public function __construct(
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        private readonly Filesystem $fileSystem,
        private readonly LoggerInterface $logger,
        private readonly string $helios_files_upload_root,
        private readonly string $helios_responses_root
    ) {
    }

    /**
     * @param int $transactionId
     * @return void
     * @throws TransactionNotFoundException
     */
    public function removeTransactionAndArtefactsAssociated(int $transactionId): void
    {
        $transaction = $this->heliosTransactionsSQL->getInfo($transactionId);

        if ($transaction === false) {
            throw new TransactionNotFoundException();
        }

        $this->removeTransaction($transaction['id']);
        $this->removeLocalFiles($transaction);
        $this->removeDistantFiles($transaction);
    }

    private function removeTransaction(string $transactionId): void
    {
        $this->heliosTransactionsSQL->delete($transactionId);
    }


    private function removeLocalFiles(array $transaction): void
    {
        $pesAllerFilePath = $this->helios_files_upload_root . '/' . $transaction['sha1'];
        $this->removeFileOnDisk($pesAllerFilePath);

        $pesRetourFilePath = $this->helios_responses_root . '/' . $transaction['acquit_filename'];
        $this->removeFileOnDisk($pesRetourFilePath);
    }

    private function removeFileOnDisk($filepath): void
    {
        $fileExists = $this->fileSystem->exists($filepath);

        if ($fileExists) {
            try {
                $this->fileSystem->remove($filepath);
            } catch (IOExceptionInterface $exception) {
                $this->logger->info(
                    'Une erreur est survenu pendant la suppression du fichier ' . $filepath . ' : ' . $exception->getMessage(
                    )
                );
            }
        }
    }

    private function removeDistantFiles(array $transaction)
    {
    }
}
