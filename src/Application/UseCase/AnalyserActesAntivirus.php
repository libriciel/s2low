<?php

namespace S2low\Application\UseCase;

use Doctrine\ORM\EntityNotFoundException;
use S2low\Domain\Service\AntivirusFilesScannerInterface;
use S2low\Infrastructure\Persistence\Repository\TransactionRepository;

class AnalyserActesAntivirus
{
    private AntivirusFilesScannerInterface $scanner;
    private TransactionRepository $transactionRepository;

    public function __construct(
        TransactionRepository $transactionRepository,
        AntivirusFilesScannerInterface $scanner
    )
    {
        $this->scanner = $scanner;
        $this->transactionRepository = $transactionRepository;
    }


    /**
     * @param $transaction_id
     * @return bool
     * @throws EntityNotFoundException
     */
    public function execute($transaction_id) : bool {
        $transaction = $this->transactionRepository->findTransactionFromActeId($transaction_id);

        try {
            $this->scanner->scan($transaction->getActeFile()->getPath());
        } catch (\Exception $exception) {
            $this->transactionRepository->updateTransactionVirusDetected($transaction);
        }
        return true;
    }
}