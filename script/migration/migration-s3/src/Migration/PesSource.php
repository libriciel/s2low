<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use App\Repository\PesRepository;
use App\Service\TransactionImportFromS2low;
use Generator;

class PesSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly PesRepository $repository
    ) {
    }

    public function getIdentifier(): string
    {
        return Type::PES_ALLER->value;
    }

    public function getItems(int $lastProcessedId): Generator
    {
        while (true) {
            $batch = $this->repository->getBatch($lastProcessedId, TransactionImportFromS2low::LIMIT);
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $item) {
                yield new MigrationItem(
                    id: $item['id'],
                    key: $item['siren'] . '/' . $item['sha1'],
                    type: $this->getIdentifier(),
                    date: $item['submission_date'],
                    siren: $item['siren']
                );
                $lastProcessedId = $item['id'];
            }
        }
    }

    public function getHandledTransactions(): Generator
    {
        // TODO: Implement getHandledTransactions() method.
    }
}
