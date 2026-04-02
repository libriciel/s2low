<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Status;
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

    public function getType(): Type
    {
        return Type::ACTE;
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
                    oldKey: $item['file_path'],
                    newKey: $item['authority_id'] . '/' . 'acte' . '/' . $item['id'] . '/' . basename($item['file_path']),
                    type: $this->getType(),
                    date: $item['submission_date'],
                    status: $item['file_path'] ? Status::HANDLE : Status::ERROR_KEY_NULL,
                );
                $lastProcessedId = $item['id'];
            }
        }
    }
}
