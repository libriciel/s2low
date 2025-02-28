<?php

namespace S2low\Infrastructure\Persistence\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use S2low\Domain\Model\Transaction\DTO\TransactionPersistenceDTO;
use S2low\Domain\Port\CloudStorageDownloaderInterface;
use S2low\Domain\Repository\TransactionRepositoryInterface;
use S2low\Infrastructure\Adapter\Enum\BucketName;
use S2low\Infrastructure\Persistence\Entity\ActesStatus;
use S2low\Infrastructure\Persistence\Entity\ActesTransactions;
use S2low\Infrastructure\Persistence\Entity\ActesTransactionsWorkflow;
use S2low\Infrastructure\Persistence\Mapper\TransactionMapper;


class TransactionActeRepository implements TransactionRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private TransactionMapper $transactionMapper;
    private CloudStorageDownloaderInterface $cloudStorageDownloader;
    private string $acteUploadRootDir;

    public function __construct(
        EntityManagerInterface $entityManager,
        TransactionMapper $transactionMapper,
        CloudStorageDownloaderInterface $cloudStorageDownloader,
        $acteUploadRootDir)
    {
        $this->entityManager = $entityManager;
        $this->transactionMapper = $transactionMapper;
        $this->cloudStorageDownloader = $cloudStorageDownloader;
        $this->acteUploadRootDir = $acteUploadRootDir;
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

        $acteWorkflows = $this->entityManager->getRepository(ActesTransactionsWorkflow::class)
            ->findBy(['transaction' => $acte]);

        $enveloppe = $acte->getEnvelope();
        $localPath = $this->acteUploadRootDir . "/" . $enveloppe->getFilePath();

        if(!file_exists($localPath))
        {
            $this->cloudStorageDownloader->downloadFile(
                BucketName::ACTE_ENVELOPPE,
                $enveloppe->getFilePath(),
                $localPath
            );
        }

        return $this->transactionMapper->mapToTransaction($acte, $enveloppe, $this->acteUploadRootDir, $acteWorkflows );
    }

    /**
     * @param TransactionPersistenceDTO $transactionPersistenceDTO
     * @return void
     */
    public function save(TransactionPersistenceDTO $transactionPersistenceDTO): void
    {

        $acteTransactions = $this->entityManager->find(ActesTransactions::class, $transactionPersistenceDTO->id);

        $acteTransactions = $this->updateActe($acteTransactions, $transactionPersistenceDTO);

        if ($acteTransactions->getLastStatusId() !== $transactionPersistenceDTO->status->value) {
            $acteTransactionsWorkflow = $this->updateActesTransactionsWorkflow($acteTransactions, $transactionPersistenceDTO);
            $this->entityManager->persist($acteTransactionsWorkflow);
        }

        $this->entityManager->persist($acteTransactions);
        $this->entityManager->flush();
    }

    private function updateActesTransactionsWorkflow(ActesTransactions $acteTransactions, TransactionPersistenceDTO $transactionPersistenceDTO): ActesTransactionsWorkflow
    {
        $lastUpdateStatus = $transactionPersistenceDTO->historyPersistenceDTO->getMostRecent();
        $actesStatus = $this->entityManager->find(ActesStatus::class, $lastUpdateStatus->status);

        $acteTransactionsWorkflow = new ActesTransactionsWorkflow();
        $acteTransactionsWorkflow->setTransaction($acteTransactions);
        $acteTransactionsWorkflow->setStatus($actesStatus);
        $acteTransactionsWorkflow->setDate(new \DateTimeImmutable());
        $acteTransactionsWorkflow->setMessage($lastUpdateStatus->message);

        return $acteTransactionsWorkflow;
    }

    private function updateActe(ActesTransactions $acteTransactions, TransactionPersistenceDTO $transactionPersistenceDTO): ActesTransactions
    {
        $acteTransactions->setLastStatusId($transactionPersistenceDTO->status->value);
        $acteTransactions->setAntivirusCheck($transactionPersistenceDTO->documentMetierDTO->antivirusChecked);

        return $acteTransactions;
    }

}