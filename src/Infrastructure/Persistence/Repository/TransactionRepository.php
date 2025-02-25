<?php

namespace S2low\Infrastructure\Persistence\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use S2low\Domain\Model\Transaction\Transaction;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Domain\Repository\TransactionRepositoryInterface;
use S2low\Entity\ActesStatus;
use S2low\Entity\ActesTransactions;
use S2low\Entity\ActesTransactionsWorkflow;
use S2low\Infrastructure\Persistence\Mapper\TransactionMapper;


class TransactionRepository implements TransactionRepositoryInterface
{

    private EntityManagerInterface $entityManager;
    private TransactionMapper $transactionMapper;

    public function __construct(EntityManagerInterface $entityManager, TransactionMapper $transactionMapper)
    {
        $this->entityManager = $entityManager;
        $this->transactionMapper = $transactionMapper;
    }

    /**
     * @param $acteId
     * @return Transaction
     * @throws EntityNotFoundException
     */
    public function findTransactionFromActeId($acteId): Transaction {
        $acte = $this->entityManager->find(ActesTransactions::class, $acteId);

        if ($acte === null) {
            throw new EntityNotFoundException("L'acte avec l'ID $acteId n'a pas été trouvé.");
        }

        $acteStatus = $this->entityManager->find(ActesStatus::class, $acte->getLastStatusId());
        $enveloppe = $acte->getEnvelope();

        return $this->transactionMapper->mapToTransaction($acte, $enveloppe, $acteStatus);
    }

    /**
     * @param Transaction $transaction
     * @return void
     */
    public function updateTransactionAnalyseAntivirusPositive(Transaction $transaction): void
    {
        match($transaction->getProtocolTransaction()) {
            ProtocolTransaction::ACTE => $this->updateActeVirusDetected($transaction),
            ProtocolTransaction::HELIOS => $this->updateHeliosVirusDetected($transaction)
        };
    }

    public function updateTransactionAnalyseAntivirusNegative(Transaction $transaction) : void
    {
        match($transaction->getProtocolTransaction()) {
            ProtocolTransaction::ACTE => $this->updateActeAnalyseAntivirusNegative($transaction),
            ProtocolTransaction::HELIOS => $this->updateHeliosAnalyseAntivirusNegative($transaction)
        };
    }


    /**
     * @param Transaction $transaction
     * @return void
     */
    private function updateActeVirusDetected(Transaction $transaction): void
    {
        $acteTransactions = $this->entityManager->find(ActesTransactions::class, $transaction->getId());
        $acteStatus = $this->entityManager->find(ActesStatus::class, StatusTransaction::ERREUR);

        $acteTransactions->setAntivirusCheck(true);
        $acteTransactions->setLastStatusId($acteStatus->getId());
        $this->entityManager->persist($acteTransactions);


        $acteTransactionsWorkflow = new ActesTransactionsWorkflow();
        $acteTransactionsWorkflow->setTransaction($acteTransactions);
        $acteTransactionsWorkflow->setStatus($acteStatus);
        $acteTransactionsWorkflow->setDate(new \DateTimeImmutable());
        $acteTransactionsWorkflow->setMessage("L'archive est infectée par un virus. Retour de l'antivirus.");
        $this->entityManager->persist($acteTransactionsWorkflow);

        $this->entityManager->flush();
    }

    private function updateHeliosVirusDetected(Transaction $transaction)
    {

    }

    private function updateActeAnalyseAntivirusNegative(Transaction $transaction): void
    {
        $acteTransactions = $this->entityManager->find(ActesTransactions::class, $transaction->getId());
        $acteTransactions->setAntivirusCheck(true);
        $this->entityManager->persist($acteTransactions);
        $this->entityManager->flush();
    }

    private function updateHeliosAnalyseAntivirusNegative(Transaction $transaction) : void
    {

    }
}