<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Status;
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

    public function getType(): Type
    {
        return Type::PES_ALLER;
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
                    oldKey: $item['sha1'],
                    newKey: $item['authority_id'] . '/pes_aller/' . $item['id'] . '/' . $item['sha1'],
                    type: $this->getType(),
                    date: $item['submission_date'],
                    status: $item['sha1'] ? Status::HANDLE : Status::ERROR_KEY_NULL,
                );
                $lastProcessedId = $item['id'];
            }
        }
    }
}
