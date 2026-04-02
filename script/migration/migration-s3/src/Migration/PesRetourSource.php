<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use App\Repository\PesRetourRepository;
use App\Service\TransactionImportFromS2low;
use Generator;

class PesRetourSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly PesRetourRepository $repository,
    ) {
    }

    public function getIdentifier(): string
    {
        return Type::PES_RETOUR->value;
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
                    oldKey: $item['filename'],
                    newKey: $item['authority_id'] . '/pes_retour/' . $item['id'] . '/' . $item['filename'],
                    type: $this->getIdentifier(),
                    date: $item['date'],
                );
                $lastProcessedId = $item['id'];
            }
        }
    }
}
