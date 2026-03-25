<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use App\Repository\PesAcquitRepository;
use App\Service\TransactionImportFromS2low;
use Generator;

class PesAcquitSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly PesAcquitRepository $repository
    ) {
    }

    public function getIdentifier(): string
    {
        return Type::PES_ACQUIT->value;
    }

    public function getItems(int $lastProcessedId, ?string $minDate = null): Generator
    {
        while (true) {
            $batch = $this->repository->getBatch($lastProcessedId, TransactionImportFromS2low::LIMIT, $minDate);
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $item) {
                yield new MigrationItem(
                    id: $item['id'],
                    key: $item['siren'] . '/' . $item['acquit_filename'],
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
