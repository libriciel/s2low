<?php

namespace S2low\Application\UseCase;

use Doctrine\ORM\EntityNotFoundException;
use Psr\Log\LoggerInterface;
use S2low\Domain\Exception\BadStatusTransactionException;
use S2low\Domain\Exception\VirusDetectedException;
use S2low\Domain\Port\AntivirusFilesScannerInterface;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Domain\Repository\TransactionRepositoryInterface;

class AnalyserActesAntivirus
{
    private AntivirusFilesScannerInterface $scanner;
    private TransactionRepositoryInterface $transactionRepository;
    private LoggerInterface $logger;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        AntivirusFilesScannerInterface $scanner,
        LoggerInterface $logger
    )
    {
        $this->scanner = $scanner;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }


    /**
     * @param $transactionId
     * @return bool
     * @throws EntityNotFoundException
     */
    public function execute($transactionId) : bool {
        $transaction = $this->transactionRepository->findTransactionFromActeId($transactionId);

        if ($transaction->getStatus() !== StatusTransaction::CREE) {
            throw new BadStatusTransactionException($transactionId, StatusTransaction::CREE, $transaction->getStatus());
        }

        $filePath = $transaction->getActeFile()->getPath();

        try {
            $this->scanner->scan($filePath);
            $this->logger->info("Le scan antivirus de la transaction ${transactionId} s'est terminé avec succès.");
        } catch (VirusDetectedException $exception) {
            $this->logger->error($exception->getMessage());
            $this->transactionRepository->updateTransactionAnalyseAntivirusPositive($transaction);

            return false;
        }

        $this->transactionRepository->updateTransactionAnalyseAntivirusNegative($transaction);

        return true;
    }
}