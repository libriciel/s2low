<?php

namespace S2low\Infrastructure\Persistence\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use S2low\Domain\Model\Transaction\DTO\TransactionPersistenceDTO;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Domain\Repository\TransactionRepositoryInterface;
use S2low\Infrastructure\Persistence\Entity\ActesStatus;
use S2low\Infrastructure\Persistence\Entity\ActesTransactions;
use S2low\Infrastructure\Persistence\Entity\ActesTransactionsWorkflow;
use S2low\Infrastructure\Persistence\Mapper\TransactionMapper;


class TransactionActeRepository implements TransactionRepositoryInterface
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
     * @return TransactionPersistenceDTO
     * @throws EntityNotFoundException
     */
    public function findById($acteId): TransactionPersistenceDTO {
        $acte = $this->entityManager->find(ActesTransactions::class, $acteId);

        if ($acte === null) {
            throw new EntityNotFoundException("L'acte avec l'ID $acteId n'a pas été trouvé.");
        }

        //TODO recuperer la liste des AcctesTransaction Workflow
        throw new \Exception("TODO A FAIRE");
            $acteWorkflow = $this->entityManager->find(ActesTransactionsWorkflow::class, $acte->getId());
        // voir ce que cela renvois


        $acteStatus = $this->entityManager->find(ActesStatus::class, $acte->getLastStatusId());
        $enveloppe = $acte->getEnvelope();

        return $this->transactionMapper->mapToTransaction($acte, $enveloppe, $acteStatus, $acteWorkflow);
    }

    /**
     * @param TransactionPersistenceDTO $transactionPersistenceDTO
     * @return void
     */
    public function save(TransactionPersistenceDTO $transactionPersistenceDTO): void
    {
        $acteTransactions = $this->entityManager->find(ActesTransactions::class, $transactionPersistenceDTO->id);
//        $acteStatus = $this->entityManager->find(ActesStatus::class, $transaction->status);

        $this->updateActe($transactionPersistenceDTO);
        $this->updateActesTransactionsWorkflow($acteTransactions, $transactionPersistenceDTO);
    }

    /**
     * @param TransactionPersistenceDTO $transactionDTO
     * @return void
     */
    public function updateTransactionAnalyseAntivirusPositive(TransactionPersistenceDTO $transactionDTO): void
    {
        $acteTransactions = $this->entityManager->find(ActesTransactions::class, $transactionDTO->getId());
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

    public function updateTransactionAnalyseAntivirusNegative(TransactionPersistenceDTO $transaction) : void
    {
        $acteTransactions = $this->entityManager->find(ActesTransactions::class, $transaction->getId());
        $acteTransactions->setAntivirusCheck(true);
        $this->entityManager->persist($acteTransactions);
        $this->entityManager->flush();
    }

    private function updateActesTransactionsWorkflow(ActesTransactions $acteTransactions, TransactionPersistenceDTO $transactionPersistenceDTO): void
    {
        if ($acteTransactions->getLastStatusId() !== $transactionPersistenceDTO->status)
        {
            $acteStatus = $this->entityManager->find(ActesStatus::class, StatusTransaction::ERREUR);

            $acteTransactionsWorkflow = new ActesTransactionsWorkflow();
            $acteTransactionsWorkflow->setTransaction($acteTransactions);
            $acteTransactionsWorkflow->setStatus($acteStatus);
            $acteTransactionsWorkflow->setDate(new \DateTimeImmutable());
            $acteTransactionsWorkflow->setMessage("L'archive est infectée par un virus. Retour de l'antivirus.");
        }
    }

    private function updateActe(TransactionPersistenceDTO $transactionPersistenceDTO)
    {
        //TODO
        throw new \Exception("TODO A FAIRE");
    }

}