<?php

namespace S2low\Infrastructure\Persistence\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use S2low\Domain\Model\Transaction\Transaction;
use S2low\Domain\Model\ValueObject\ProtocolTransaction;
use S2low\Entity\ActesStatus;
use S2low\Entity\ActesTransactions;
use S2low\Entity\ActesTransactionsWorkflow;
use S2low\Infrastructure\Persistence\Mapper\TransactionMapper;


class TransactionRepository
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
        $enveloppe = $acte->getEnveloppe();

        return TransactionMapper::mapToTransaction($acte, $enveloppe, $acteStatus);
    }

    /**
     * @param Transaction $transaction
     * @return void
     */
    public function updateTransactionVirusDetected(Transaction $transaction): void
    {
        match($transaction->getProtocolTransaction()) {
            ProtocolTransaction::ACTE => $this->updateActeVirusDetected($transaction),
            ProtocolTransaction::HELIOS => $this->updateHeliosVirusDetected($transaction)
        };
    }

    /**
     * @param Transaction $transaction
     * @return void
     */
    private function updateActeVirusDetected(Transaction $transaction): void
    {
        $acteTransactions = $this->entityManager->find(ActesTransactions::class, $transaction->getId());
        $acteStatus = $this->entityManager->find(ActesStatus::class, $transaction->getStatus()->value);

        $acteTransactions->setAntivirusCheck(true);
        $acteTransactions->setLastStatusId($acteStatus->getId());
        $this->entityManager->persist($acteTransactions);


        $acteTransactionsWorkflow = new ActesTransactionsWorkflow();
        $acteTransactionsWorkflow->setTransaction($acteTransactions);
        $acteTransactionsWorkflow->setStatus($acteStatus);
        $acteTransactionsWorkflow->setMessage("Un virus a été trouvé pour la transaction ". $transaction->getId());
        $this->entityManager->persist($acteTransactionsWorkflow);

        $this->entityManager->flush();
    }

    private function updateHeliosVirusDetected(Transaction $transaction)
    {

    }
}