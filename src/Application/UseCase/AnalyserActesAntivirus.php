<?php

namespace S2low\Application\UseCase;

use Psr\Log\LoggerInterface;
use S2low\Domain\Exception\BadStatusTransactionException;
use S2low\Domain\Exception\DocumentMetierNotFoundException;
use S2low\Domain\Exception\VirusDetectedException;
use S2low\Domain\Port\AntivirusFilesScannerInterface;
use S2low\Domain\Repository\TransactionRepositoryInterface;

class AnalyserActesAntivirus
{
    private AntivirusFilesScannerInterface $antivirus;
    private TransactionRepositoryInterface $transactionRepository;
    private LoggerInterface $logger;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        AntivirusFilesScannerInterface $scanner,
        LoggerInterface $logger,
    ) {
        $this->antivirus = $scanner;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }


    /**
     * @param $transactionId
     * @return void
     */
    public function execute($transactionId): void
    {
        $transactionDTO = $this->transactionRepository->findById($transactionId);
        $transaction = $transactionDTO->toModel();

        try {
            $transaction->readyToBeScannedOrThrow();

            $this->antivirus->scan(
                $transaction->getArchive()->getAbsolutePath()
            );

            $transaction->confirmVirusAbsence();

            $this->logger->info("Le scan antivirus de la transaction {transactionId} s'est terminé avec succès.", [
                'transactionId' => $transactionId
            ]);
        } catch (VirusDetectedException $exception) {
            $transaction->reportVirusPresence();
            $this->logger->warning($exception->getMessage());
        } catch (
            BadStatusTransactionException |
            DocumentMetierNotFoundException $exception
        ) {
            $this->logger->warning($exception->getMessage());
        }

        $transactionDto = $transaction->toPersistenceDto();
        $this->transactionRepository->save($transactionDto);
    }
}
