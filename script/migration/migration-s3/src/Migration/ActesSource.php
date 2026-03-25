<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use App\Repository\ActesRepository;
use App\Service\TransactionImportFromS2low;
use Generator;

class ActesSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly ActesRepository $repository
    ) {
    }

    public function getIdentifier(): string
    {
        return Type::ACTE->value;
    }

    public function getItems(int $lastProcessedId, ?string $minDate = null, ?string $maxDate = null): Generator
    {
        while (true) {
            $batch = $this->repository->getBatch($lastProcessedId, TransactionImportFromS2low::LIMIT, $minDate, $maxDate);
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $item) {
                yield new MigrationItem(
                    id: $item['id'],
                    key: $item['file_path'],
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
