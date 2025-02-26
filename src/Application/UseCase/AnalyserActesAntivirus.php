<?php

namespace S2low\Application\UseCase;

use Doctrine\ORM\EntityNotFoundException;
use Psr\Log\LoggerInterface;
use S2low\Domain\Exception\VirusDetectedException;
use S2low\Domain\Port\AntivirusFilesScannerInterface;
use S2low\Domain\Repository\TransactionRepositoryInterface;

class AnalyserActesAntivirus
{
    private AntivirusFilesScannerInterface $scanner;
    private TransactionRepositoryInterface $transactionRepository;
    private LoggerInterface $logger;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        AntivirusFilesScannerInterface $scanner,
        LoggerInterface                $logger,
    )
    {
        $this->scanner = $scanner;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }


    /**
     * @param $transactionId
     * @return void
     * @throws EntityNotFoundException
     */
    public function execute($transactionId) : void
    {
        $transactionDTO = $this->transactionRepository->findById($transactionId);
        $transaction = $transactionDTO->toModel();

        try {
            $transaction->scanWith($this->scanner);

            $transaction->analyseAntivirusPositive();
            $this->logger->info("Le scan antivirus de la transaction {transactionId} s'est terminé avec succès.", [
                'transactionId' => $transactionId
            ]);

        } catch (VirusDetectedException $exception) {
            $transaction->analyseAntivirusNegative();
            $this->logger->warning($exception->getMessage());
        }

        $transactionDto = $transaction->toPersistenceDto();
        $this->transactionRepository->save($transactionDto);
    }
}